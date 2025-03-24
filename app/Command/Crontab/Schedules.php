<?php

namespace App\Command\Crontab;

use App\Util\ConsoleLog;
use Cron\CronExpression;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Context\ApplicationContext;
use Psr\Container\ContainerInterface;
use Swoole\Process;

#[Command]
class Schedules extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('crontab:schedules');
    }

    public function configure(): void
    {
        parent::configure();
        // 指令配置
        $this->setDescription('根据配置的Crontab规则，执行定时脚本');
    }

    public function handle(): void
    {

        $console = ApplicationContext::getContainer()->get(ConsoleLog::class);

        // 指令输出
        $parent_pid = getmypid();//父进程ID
        $console->info(sprintf('[%s] Execute start! %s', $parent_pid, date('Y-m-d H:i:s')));
        $exec_crontab_arr = [];

        $time = time();
        //筛选未过期且未完成的任务
        $crontabList = Db::name('crontab_task')->where('status', '=', '1')->order('weigh DESC,id DESC')->select();

        foreach ($crontabList as $key => $crontab) {
            if ($time < $crontab['begin_time']) {
                //$output->writeln($crontab['id'] . '-result:' . '任务未开始');
                continue;
            }
            if ($crontab['end_time'] > 0 && $time > $crontab['end_time']) {
                //任务已过期
                //$output->writeln($crontab['id'] . '-result:' . '任务已过期');
                continue;
            }

            //如果未到执行时间则继续循环
            $cron = new CronExpression($crontab['command']);
            if (! $cron->isDue(date("YmdHis", $time))) {
                //$output->writeln($crontab['id'] . '-result:' . '未到执行时间则继续循环');
                continue;
            }
            $exec_crontab_arr[] = $crontab;
        }
        //php命令路径
        $phpPath = trim(shell_exec('which php') ?: shell_exec('where php'));

        $that = $this;

        foreach ($exec_crontab_arr as $crontab) {
            $process = new Process(function (\Swoole\Process $childProcess) use ($phpPath, $crontab, $parent_pid, $console, $that) {
                $console->info(sprintf('[%s] Child #%s start #%s', $parent_pid, $childProcess->pid, $crontab['id']));
//                $childProcess->exec($phpPath, [app()->getRootPath() . 'think', 'taskExec', $crontab['class_path'], $crontab['title']]);
                var_dump($crontab['command']);
                $that->call($crontab['command']);
                $console->writeln(sprintf('[%s] Child #%s exit #%s', $parent_pid, $childProcess->pid, $crontab['id']));
            });
            $process->start();
        }

        $count = count($exec_crontab_arr);
        for ($n = $count; $n--;) {
            $status = Process::wait(true);
            $console->info(sprintf('[%s] Recycled #%s  code=%s, signal=%s', $parent_pid, $status['pid'], $status['code'], $status['signal']));
        }

        // 指令输出
        $console->info(sprintf('[%s] Execute completed! %s', $parent_pid, date('Y-m-d H:i:s')));
    }

}