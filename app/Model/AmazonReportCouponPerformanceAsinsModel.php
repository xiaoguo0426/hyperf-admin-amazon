<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Model;

/**
 * Class AmazonReportCouponPerformanceAsinModel.
 *
 * @property $id
 * @property $merchant_id
 * @property $merchant_store_id
 * @property $coupon_performance_id
 * @property $asin
 * @property $seller_sku
 * @property $created_at
 * @property $updated_at
 */
class AmazonReportCouponPerformanceAsinsModel extends Model
{
    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';

    protected ?string $table = 'amazon_report_coupon_performance_asins';
}
