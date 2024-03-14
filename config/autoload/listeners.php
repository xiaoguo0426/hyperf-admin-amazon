<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */
use Hyperf\Command\Listener\FailToHandleListener;
use Hyperf\ExceptionHandler\Listener\ErrorExceptionHandler;

/*
 * @contact  740644717@qq.com
 * @license  MIT
 */
return [
    ErrorExceptionHandler::class,
    FailToHandleListener::class,
];
