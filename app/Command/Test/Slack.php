<?php

namespace App\Command\Test;

use App\Kernel\Log;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;

use function Hyperf\Support\env;

#[Command]
class Slack extends HyperfCommand
{

    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('test:slack');
    }

    public function handle(): void
    {

//        var_dump(pathinfo('https://www.baidu.com/a/n/c.php', PATHINFO_EXTENSION));
//        var_dump(env('LOG_SLACK_WEBHOOK_URL'));

        $res = Log::get('test', 'slack')->error('这条信息将会发送到Slack');

        var_dump($res);
    }

}