<?php

declare(strict_types=1);

/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

use Hyperf\Devtool\Generator\GeneratorCommand;

return [
    'scan' => [
        'paths' => [
            BASE_PATH . '/app',
        ],
        'ignore_annotations' => [
            'mixin',
        ],
        'class_map' => [
//            GeneratorCommand::class => BASE_PATH . '/class_map/MyGeneratorCommand.php',
        ]
    ],
];
