<?php

namespace App\Command\Test;

use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Context\ApplicationContext;
use Psr\Container\ContainerInterface;

#[Command]
class ConsoleLog extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('test:console-log');
    }

    public function handle(): void
    {

        $console = ApplicationContext::getContainer()->get(\App\Util\ConsoleLog::class);

        $console->debug('这是一个debug日志',['data'=>'123']);
        $console->info('这是一个info日志');
        $console->notice('这是一个notice日志');
        $console->warning('这是一个warning日志');
        $console->error('这是一个error日志');
        $console->critical('这是一个critical日志');
        $console->alert('这是一个alert日志');

    }

}