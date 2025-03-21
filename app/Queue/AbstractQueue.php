<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Queue;

use App\Kernel\Redis;
use App\Queue\Data\QueueDataInterface;
use App\Util\Prefix;

abstract class AbstractQueue implements QueueInterface
{
    protected ?object $redis;

    protected string $queue_name;

    protected int $timeout = 3;

    protected int $retryInterval = 10;

    /**
     * 是否记录队列数据处理耗时.
     */
    protected bool $isLogHandleDataTime = false;

    public function __construct()
    {
        $this->queue_name = Prefix::queue() . $this->getQueueName();
        $this->redis = Redis::get();
    }

    abstract public function getQueueName(): string;

    abstract public function getQueueDataClass(): string;

    abstract public function push(QueueDataInterface $queueData): bool;

    abstract public function pop(): bool;

    abstract public function handleQueueData(QueueDataInterface $queueData): bool;

    abstract public function len(): int;

    /**
     * 队列安全线  0为不检测。大于0则会判断该队列当前长度是否超过安全线设置.
     */
    public function safetyLine(): int
    {
        return 0;
    }
}
