<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

use function Hyperf\Support\env;

return [
    'report_template_path' => env('AMAZON_REPORT_TEMPLATE_PATH'),
    'report_lang' => [
        'MXN' => [
            'Pedido' => 'Order',
            'Tarifa de servicio' => 'Service fee',
            'Trasferir' => 'Transfer',
            'Reembolso' => 'Refund',
            'Suscripción' => 'Subscription',
        ]
    ]
];
