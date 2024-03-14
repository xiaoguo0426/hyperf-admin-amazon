<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */
use App\Exception\Handler\AppExceptionHandler;
use Hyperf\HttpServer\Exception\Handler\HttpExceptionHandler;

/*
 * @contact  740644717@qq.com
 * @license  MIT
 */
return [
    'handler' => [
        'http' => [
            HttpExceptionHandler::class,
            AppExceptionHandler::class,
        ],
    ],
];
