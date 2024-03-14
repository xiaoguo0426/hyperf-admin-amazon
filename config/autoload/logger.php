<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

/**
 * @contact  740644717@qq.com
 * @license  MIT
 */
return [
    'default' => [
        'handler' => [
            'class' => StreamHandler::class,
            'constructor' => [
                'stream' => BASE_PATH . '/runtime/logs/hyperf.log',
                'level' => Level::Debug,
            ],
        ],
        'formatter' => [
            'class' => LineFormatter::class,
            'constructor' => [
                'format' => null,
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
            ],
        ],
    ],
    'sql' => [
        'handler' => [
            'class' => RotatingFileHandler::class,
            'constructor' => [
                'dateFormat' => 'Y-m-d',
                'filenameFormat' => '{date}',
                'filename' => BASE_PATH . '/runtime/logs/sql/.log',
            ],
        ],
        'formatter' => [
            'class' => LineFormatter::class,
            'constructor' => [
                'format' => null,
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
            ],
        ],
    ],
    // 队列日志 日志文件按日期轮转
    'queue' => [
        'handler' => [
            'class' => RotatingFileHandler::class,
            'constructor' => [
                'dateFormat' => 'Y-m-d',
                'filenameFormat' => '{date}',
                'filename' => BASE_PATH . '/runtime/logs/queue/.log',
            ],
        ],
        'formatter' => [
            'class' => LineFormatter::class,
            'constructor' => [
                'format' => null,
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
            ],
        ],
    ],
    'amazon-catalog' => [
        'handler' => [
            'class' => RotatingFileHandler::class,
            'constructor' => [
                'dateFormat' => 'Y-m-d',
                'filenameFormat' => '{date}',
                'filename' => BASE_PATH . '/runtime/logs/amazon-catalog/.log',
            ],
        ],
        'formatter' => [
            'class' => LineFormatter::class,
            'constructor' => [
                'format' => null,
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
            ],
        ],
    ],
    // Amazon Report 日志文件按日期轮转
    'amazon-report' => [
        'handler' => [
            'class' => RotatingFileHandler::class,
            'constructor' => [
                'dateFormat' => 'Y-m-d',
                'filenameFormat' => '{date}',
                'filename' => BASE_PATH . '/runtime/logs/amazon-report/.log',
            ],
        ],
        'formatter' => [
            'class' => LineFormatter::class,
            'constructor' => [
                'format' => null,
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
            ],
        ],
    ],
    'amazon-report-document' => [
        'handler' => [
            'class' => RotatingFileHandler::class,
            'constructor' => [
                'dateFormat' => 'Y-m-d',
                'filenameFormat' => '{date}',
                'filename' => BASE_PATH . '/runtime/logs/amazon-report-document/.log',
            ],
        ],
        'formatter' => [
            'class' => LineFormatter::class,
            'constructor' => [
                'format' => null,
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
            ],
        ],
    ],
    'amazon-finance' => [
        'handler' => [
            'class' => RotatingFileHandler::class,
            'constructor' => [
                'dateFormat' => 'Y-m-d',
                'filenameFormat' => '{date}',
                'filename' => BASE_PATH . '/runtime/logs/amazon-finance/.log',
            ],
        ],
        'formatter' => [
            'class' => LineFormatter::class,
            'constructor' => [
                'format' => null,
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
            ],
        ],
    ],
    'amazon-fba' => [
        'handler' => [
            'class' => RotatingFileHandler::class,
            'constructor' => [
                'dateFormat' => 'Y-m-d',
                'filenameFormat' => '{date}',
                'filename' => BASE_PATH . '/runtime/logs/amazon-fba/.log',
            ],
        ],
        'formatter' => [
            'class' => LineFormatter::class,
            'constructor' => [
                'format' => null,
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
            ],
        ],
    ],
    'amazon-sales' => [
        'handler' => [
            'class' => RotatingFileHandler::class,
            'constructor' => [
                'dateFormat' => 'Y-m-d',
                'filenameFormat' => '{date}',
                'filename' => BASE_PATH . '/runtime/logs/amazon-sales/.log',
            ],
        ],
        'formatter' => [
            'class' => LineFormatter::class,
            'constructor' => [
                'format' => null,
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
            ],
        ],
    ],
    'amazon-sellers' => [
        'handler' => [
            'class' => RotatingFileHandler::class,
            'constructor' => [
                'dateFormat' => 'Y-m-d',
                'filenameFormat' => '{date}',
                'filename' => BASE_PATH . '/runtime/logs/amazon-sellers/.log',
            ],
        ],
        'formatter' => [
            'class' => LineFormatter::class,
            'constructor' => [
                'format' => null,
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
            ],
        ],
    ],
    'amazon-fulfillment-inbound' => [
        'handler' => [
            'class' => RotatingFileHandler::class,
            'constructor' => [
                'dateFormat' => 'Y-m-d',
                'filenameFormat' => '{date}',
                'filename' => BASE_PATH . '/runtime/logs/amazon-fulfillment-inbound/.log',
            ],
        ],
        'formatter' => [
            'class' => LineFormatter::class,
            'constructor' => [
                'format' => null,
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
            ],
        ],
    ],
    'amazon-fulfillment-outbound' => [
        'handler' => [
            'class' => RotatingFileHandler::class,
            'constructor' => [
                'dateFormat' => 'Y-m-d',
                'filenameFormat' => '{date}',
                'filename' => BASE_PATH . '/runtime/logs/amazon-fulfillment-outbound/.log',
            ],
        ],
        'formatter' => [
            'class' => LineFormatter::class,
            'constructor' => [
                'format' => null,
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
            ],
        ],
    ],
    'amazon-orders' => [
        'handler' => [
            'class' => RotatingFileHandler::class,
            'constructor' => [
                'dateFormat' => 'Y-m-d',
                'filenameFormat' => '{date}',
                'filename' => BASE_PATH . '/runtime/logs/amazon-orders/.log',
            ],
        ],
        'formatter' => [
            'class' => LineFormatter::class,
            'constructor' => [
                'format' => null,
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
            ],
        ],
    ],
    'amazon-messaging' => [
        'handler' => [
            'class' => RotatingFileHandler::class,
            'constructor' => [
                'dateFormat' => 'Y-m-d',
                'filenameFormat' => '{date}',
                'filename' => BASE_PATH . '/runtime/logs/amazon-messaging/.log',
            ],
        ],
        'formatter' => [
            'class' => LineFormatter::class,
            'constructor' => [
                'format' => null,
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
            ],
        ],
    ],
    'amazon-listing' => [
        'handler' => [
            'class' => RotatingFileHandler::class,
            'constructor' => [
                'dateFormat' => 'Y-m-d',
                'filenameFormat' => '{date}',
                'filename' => BASE_PATH . '/runtime/logs/amazon-listing/.log',
            ],
        ],
        'formatter' => [
            'class' => LineFormatter::class,
            'constructor' => [
                'format' => null,
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
            ],
        ],
    ],
    'amazon-product-pricing' => [
        'handler' => [
            'class' => RotatingFileHandler::class,
            'constructor' => [
                'dateFormat' => 'Y-m-d',
                'filenameFormat' => '{date}',
                'filename' => BASE_PATH . '/runtime/logs/amazon-product-pricing/.log',
            ],
        ],
        'formatter' => [
            'class' => LineFormatter::class,
            'constructor' => [
                'format' => null,
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
            ],
        ],
    ],
];
