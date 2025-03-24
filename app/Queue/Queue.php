<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Queue;

use App\Queue\Data\QueueData;
use App\Queue\Data\QueueDataInterface;
use App\Util\Log\QueueLog;
use Hyperf\Collection\Collection;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Coroutine\Exception\ParallelExecutionException;
use Hyperf\Coroutine\Parallel;
use Hyperf\Engine\Coroutine;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class Queue extends AbstractQueue
{
    public function getQueueName(): string
    {
        return '';
    }

    /**
     * @throws \RedisException
     */
    public function push(QueueDataInterface $queueData): bool
    {
        return (bool) $this->redis->lpush($this->queue_name, $queueData->toJson());
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \JsonException
     * @throws \RedisException
     */
    public function pop(): bool
    {
        $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
        $logger = ApplicationContext::getContainer()->get(QueueLog::class);

        $pid = posix_getpid();

        $process_title = $this->queue_name . '-' . $pid;
        cli_set_process_title($process_title);
        // swoole下无法做到进程平滑退出
        $signal_handler = static function ($sig_no) use ($console): void {
            $pid = posix_getpid();
            $title = cli_get_process_title();
            $console->warning(sprintf('进程[%s] pid:%s 收到 %s 命令，进程退出...', $title, $pid, $sig_no));
            exit;
        };

        pcntl_signal(SIGTERM, $signal_handler); // kill -15 pid 信号值:15
        pcntl_signal(SIGINT, $signal_handler); // Ctrl-C        信号值:2  方便本地调试
        pcntl_signal(SIGUSR1, $signal_handler); // 自定义信号     信号值:10
        pcntl_signal(SIGUSR2, $signal_handler); // 自定义信号     信号值:12

        $timeout = $this->timeout;

        $retryInterval = $this->retryInterval; // 消息重试次数
        while (true) {
            try {
                $pop = $this->redis->brpop($this->queue_name, $timeout);
                if (empty($pop)) {
                    pcntl_signal_dispatch();
                    $console->info(sprintf('进程[%s] pid:%s 队列为空，自动退出', cli_get_process_title(), $pid));
                    break;
                }
            } catch (\RedisException $exception) {
                $logger->error(sprintf('队列：%s 连接Redis异常.%s', $this->queue_name, $exception->getMessage()));
                break;
            }

            $data = $pop[1];
            $logger->info(sprintf('队列：%s 消费数据. data:%s', $this->queue_name, $data));
            //            $decode = json_decode($data, true);
            //            if (json_last_error() !== JSON_ERROR_NONE) {
            //                Log::record('队列：' . $this->queue_name . ' 数据格式不是合法的JSON格式. data:' . $data);
            //                continue;
            //            }

            $class = $this->getQueueDataClass();
            /**
             * @var QueueData $dataObject
             */
            $dataObject = new $class();

            $arr = $dataObject->toArr($data);
            $dataObject->parse($arr);

            $t1 = microtime(true);
            $handle = $this->handleQueueData($dataObject);
            $t2 = microtime(true);

            if ($this->isLogHandleDataTime) {
                $logger->info(sprintf('队列：%s 消费数据. data:%s  耗时:%s 秒', $this->queue_name, $data, round($t2 - $t1, 3)));
            }

            if ($handle === false) {
                $retry = $dataObject->getRetry();
                if ($retry < $retryInterval) {
                    ++$retry;

                    $dataObject->setRetry($retry);
                    $json = $dataObject->toJson();

                    $logger->warning(sprintf('队列：%s  消费失败，重新入队. data:%s', $this->queue_name, $json));

                    $this->push($dataObject);
                }
            } else {
                $logger->info(sprintf('队列：%s 消费成功. data:%s', $this->queue_name, $data));
            }

            pcntl_signal_dispatch();
        }
        return true;
    }

    public function coPop(int $parallel_num = 100): bool
    {

        $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
        $logger = ApplicationContext::getContainer()->get(QueueLog::class);

        if ($parallel_num < 1) {
            $console->error(sprintf('队列:%s 并行消费数量不能小于1。当前并行数量为:%s', $this->queue_name, $parallel_num));
            return false;
        }

        $pid = posix_getpid();

        $process_title = $this->queue_name . '-' . $pid;
        cli_set_process_title($process_title);

        $timeout = $this->timeout;

        $retryInterval = $this->retryInterval; // 消息重试次数

        while ($this->len()) {
            $collections = new Collection();

            $count = 0;

            while (true) {
                try {
                    $pop = $this->redis->brpop($this->queue_name, $timeout);
                    if (empty($pop)) {
                        break;
                    }
                    $collections->push($pop[1]);
                } catch (\RedisException $exception) {
                    $logger->error(sprintf('队列：%s 连接Redis异常.%s', $this->queue_name, $exception->getMessage()));
                    break;
                }

                $count = $collections->count();
                if ($count >= $parallel_num) {
                    break;
                }
            }

            $console->notice(sprintf('进程[%s] pid:%s 消费数据长度%s', cli_get_process_title(), $pid, $count));
            if ($count === 0) {
                return true;
            }

            $class = $this->getQueueDataClass();

            $parallel = new Parallel($parallel_num);

            $that = $this;
            /**
             * @var QueueData $dataObject
             */
            $collections->each(function ($item) use ($that, $class, $parallel, $logger, $console) {
                /**
                 * @var QueueData $dataObject
                 */
                $dataObject = new $class();

                $arr = $dataObject->toArr($item);
                $dataObject->parse($arr);

                $parallel->add(function () use ($that, $dataObject, $item, $console) {

                    $t1 = microtime(true);

                    $handle = $that->handleQueueData($dataObject);
                    if ($handle === false) {
                    }

                    $t2 = microtime(true);

                    $co_id = Coroutine::id();//当前协程id

                    $console->info(sprintf('队列：%s 消费数据. data:%s  耗时:%s 秒 co_id:%s', $this->queue_name, json_encode($item, JSON_THROW_ON_ERROR), round($t2 - $t1, 3), $co_id));

                    return $co_id;
                });
            });

            try{
                $results = $parallel->wait();
            } catch(ParallelExecutionException $e){
                // $e->getResults() 获取协程中的返回值。
                // $e->getThrowables() 获取协程中出现的异常。
            }

        }
        return true;
    }

    /**
     * @throws \RedisException
     */
    public function len(): int
    {
        return (int) $this->redis->llen($this->queue_name);
    }

    public function handleQueueData(QueueDataInterface $queueData): bool
    {
        throw new \RuntimeException('请在子类实现 handleQueueData 方法');
    }

    public function getQueueDataClass(): string
    {
        throw new \RuntimeException('请在子类实现 getQueueDataClass 方法');
    }
}
