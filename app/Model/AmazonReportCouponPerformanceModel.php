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
 * Class AmazonReportCouponPerformanceModel.
 *
 * @property $id
 * @property $merchant_id
 * @property $merchant_store_id
 * @property $coupon_id
 * @property $amazon_merchant_id
 * @property $marketplace_id
 * @property $currency_code
 * @property $name
 * @property $website_message
 * @property $start_date_time
 * @property $end_date_time
 * @property $discount_type
 * @property $discount_amount
 * @property $total_discount
 * @property $clips
 * @property $redemptions
 * @property $budget
 * @property $budget_spent
 * @property $budget_remaining
 * @property $budget_percentage_used
 * @property $budget_sales
 * @property $created_at
 * @property $updated_at
 */
class AmazonReportCouponPerformanceModel extends Model
{
    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';

    protected ?string $table = 'amazon_report_coupon_performance';
}
