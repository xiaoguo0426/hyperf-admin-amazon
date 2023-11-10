<?php

declare(strict_types=1);

/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */
return [
    'ide' => \Hyperf\Support\env('DEVTOOL_IDE', ''),
    'generator' => [
        'amqp' => [
            'consumer' => [
                'namespace' => 'App\\Amqp\\Consumer',
            ],
            'producer' => [
                'namespace' => 'App\\Amqp\\Producer',
            ],
        ],
        'aspect' => [
            'namespace' => 'App\\Aspect',
        ],
        'command' => [
            'namespace' => 'App\\Command',
        ],
        'controller' => [
            'namespace' => 'App\\Controller',
        ],
        'model' => [
            'namespace' => 'App\\Model',
            'stub' => BASE_PATH . '/config/stubs/model.stub',
        ],
        'job' => [
            'namespace' => 'App\\Job',
        ],
        'listener' => [
            'namespace' => 'App\\Listener',
        ],
        'middleware' => [
            'namespace' => 'App\\Middleware',
        ],
        'Process' => [
            'namespace' => 'App\\Processes',
        ],
    ],
];
