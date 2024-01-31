<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Util\Amazon\Finance;

use function Hyperf\Support\make;

class FinanceFactory
{
    public static function getInstance(int $merchant_id, int $merchant_store_id, string $class): FinanceBase
    {
        return make($class, [$merchant_id, $merchant_store_id]);
    }
}
