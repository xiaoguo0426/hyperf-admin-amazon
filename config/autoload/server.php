<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */
use Hyperf\Framework\Bootstrap\PipeMessageCallback;
use Hyperf\Framework\Bootstrap\WorkerExitCallback;
use Hyperf\Framework\Bootstrap\WorkerStartCallback;
use Hyperf\HttpServer\Server;
use Hyperf\Server\Event;
use Hyperf\Server\ServerInterface;
use Swoole\Constant;

return [
    'mode' => SWOOLE_PROCESS,//运行模式 https://wiki.swoole.com/zh-cn/#/learn?id=server%e7%9a%84%e4%b8%89%e7%a7%8d%e8%bf%90%e8%a1%8c%e6%a8%a1%e5%bc%8f%e4%bb%8b%e7%bb%8d
    'servers' => [
        [
            'name' => 'http',
            'type' => ServerInterface::SERVER_HTTP,
            'host' => '0.0.0.0',
            'port' => 9501,
            'sock_type' => SWOOLE_SOCK_TCP,
            'callbacks' => [
                Event::ON_REQUEST => [Server::class, 'onRequest'],
            ],
            'options' => [
                // Whether to enable request lifecycle event
                'enable_request_lifecycle' => true,
            ],
        ],
    ],
    'settings' => [
        Constant::OPTION_ENABLE_COROUTINE => true,// 开启内置协程
        Constant::OPTION_WORKER_NUM => swoole_cpu_num(),// 设置启动的 Worker 进程数
        Constant::OPTION_PID_FILE => BASE_PATH . '/runtime/hyperf.pid',// master 进程的 PID
        Constant::OPTION_OPEN_TCP_NODELAY => true,//TCP 连接发送数据时会关闭 Nagle 合并算法，立即发往客户端连接
        Constant::OPTION_MAX_COROUTINE => 100000,//设置当前工作进程最大协程数量
        Constant::OPTION_OPEN_HTTP2_PROTOCOL => true,//启用 HTTP2 协议解析
        Constant::OPTION_MAX_REQUEST => 100000,//设置 worker 进程的最大任务数
        Constant::OPTION_SOCKET_BUFFER_SIZE => 2 * 1024 * 1024,//配置客户端连接的缓存区长度
        Constant::OPTION_BUFFER_OUTPUT_SIZE => 2 * 1024 * 1024,
    ],
    'callbacks' => [
        Event::ON_WORKER_START => [WorkerStartCallback::class, 'onWorkerStart'],
        Event::ON_PIPE_MESSAGE => [PipeMessageCallback::class, 'onPipeMessage'],
        Event::ON_WORKER_EXIT => [WorkerExitCallback::class, 'onWorkerExit'],
    ],
];
