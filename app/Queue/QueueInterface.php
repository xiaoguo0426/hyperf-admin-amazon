<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Queue;

use App\Queue\Data\QueueDataInterface;

interface QueueInterface
{
    public function getQueueName(): string;

    public function getQueueDataClass(): string;

    public function push(QueueDataInterface $queueData): bool;

    public function pop(): bool;

    public function coPop(): bool;

    public function handleQueueData(QueueDataInterface $queueData): bool;
}
