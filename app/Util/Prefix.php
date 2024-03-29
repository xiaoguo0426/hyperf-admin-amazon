<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Util;

use Carbon\Carbon;

use function Hyperf\Config\config;

class Prefix
{
    public static function amazonApp(int $merchant_id, int $merchant_store_id): string
    {
        return 'amazon-app:' . $merchant_id . ':' . $merchant_store_id;
    }

    public static function amazonAccessToken(int $merchant_id, int $merchant_store_id, string $region): string
    {
        return 'amazon-access-token:' . $merchant_id . ':' . $merchant_store_id . ':' . $region;
    }

    public static function amazonSessionToken(int $merchant_id, int $merchant_store_id, string $region): string
    {
        return 'amazon-session-token:' . $merchant_id . ':' . $merchant_store_id . ':' . $region;
    }

    public static function queue(): string
    {
        return config('app_name') . ':queue:';
    }

    public static function amazonAsinSaleVolume(int $merchant_id, string $type): string
    {
        return 'amazon-asin-sales-volume:' . $merchant_id . ':' . $type;
    }

    public static function amazonAsinFbaFee(int $merchant_id, int $merchant_store_id, string $currency): string
    {
        return 'amazon-asin-fba-fee:' . $currency . ':' . $merchant_id . ':' . $merchant_store_id;
    }

    public static function amazonReportMarkCanceled(int $merchant_id, int $merchant_store_id): string
    {
        return sprintf('amazon-report-mark-canceled:%s:%s:%s', Carbon::now()->format('Ymd'), $merchant_id, $merchant_store_id);
    }

    public static function amazonInventoryFnSkuMap(int $merchant_id, int $merchant_store_id): string
    {
        return sprintf('amazon-inventory-fnsku-sku-map:%s:%s', $merchant_id, $merchant_store_id);
    }
}
