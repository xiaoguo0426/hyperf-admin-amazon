<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Kernel;

use Hyperf\Redis\RedisFactory;
use Hyperf\Redis\RedisProxy;

class Redis
{
    /**
     * @param mixed $group
     * @return RedisProxy
     */
    public static function get(mixed $group = 'default'): RedisProxy
    {
        return di(RedisFactory::class)->get($group);
    }
}
