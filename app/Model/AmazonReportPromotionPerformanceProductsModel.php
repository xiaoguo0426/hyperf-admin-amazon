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
 * Class AmazonReportPromotionPerformanceProductsModel.
 *
 * @property $id
 * @property $merchant_id
 * @property $merchant_store_id
 * @property $promotion_performance_id
 * @property $asin
 * @property $seller_sku
 * @property $product_name
 * @property $product_glance_views
 * @property $product_units_sold
 * @property $product_revenue
 * @property $product_revenue_currency_code
 * @property $created_at
 * @property $updated_at
 */
class AmazonReportPromotionPerformanceProductsModel extends Model
{
    protected ?string $table = 'amazon_report_promotion_performance_products';

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';

}
