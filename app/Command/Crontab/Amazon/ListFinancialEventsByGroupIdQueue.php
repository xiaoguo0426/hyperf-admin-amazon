<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Command\Crontab\Amazon;

use App\Queue\AmazonFinanceFinancialListEventsByGroupIdQueue;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use JsonException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RedisException;

#[Command]
class ListFinancialEventsByGroupIdQueue extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('crontab:amazon:list-financial-events-by-group-id');
    }

    public function configure(): void
    {
        parent::configure();
        $this->setDescription('Amazon Finance List Financial Events By Group Id Queue Command');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws JsonException
     * @throws RedisException
     * @return void
     */
    public function handle(): void
    {
        (new AmazonFinanceFinancialListEventsByGroupIdQueue())->pop();
    }
}
