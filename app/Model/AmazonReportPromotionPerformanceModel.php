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
 * Class AmazonReportPromotionPerformanceModel.
 *
 * @property $id
 * @property $merchant_id
 * @property $merchant_store_id
 * @property $promotion_id
 * @property $promotion_name
 * @property $marketplace_id
 * @property $amazon_merchant_id
 * @property $type
 * @property $status
 * @property $glance_views
 * @property $units_sold
 * @property $revenue
 * @property $revenue_currency_code
 * @property $start_date_time
 * @property $end_date_time
 * @property $created_date_time
 * @property $last_updated_date_time
 * @property $created_at
 * @property $updated_at
 */
class AmazonReportPromotionPerformanceModel extends Model
{
    protected ?string $table = 'amazon_report_promotion_performance';

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';

}
