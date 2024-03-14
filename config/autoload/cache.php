<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */
use Hyperf\Cache\Driver\RedisDriver;
use Hyperf\Codec\Packer\PhpSerializerPacker;

/*
 * @contact  740644717@qq.com
 * @license  MIT
 */
return [
    'default' => [
        'driver' => RedisDriver::class,
        //        'packer' => Hyperf\Utils\Packer\PhpSerializerPacker::class,
        'packer' => PhpSerializerPacker::class,
        'prefix' => 'c:',
    ],
];
