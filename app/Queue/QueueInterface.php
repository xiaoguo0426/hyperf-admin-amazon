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
    public function getQueueName(): void;

    public function getQueueDataClass(): string;

    public function push(QueueDataInterface $queueData): void;

    public function pop(): void;

    public function handleQueueData(QueueDataInterface $queueData): void;
}
