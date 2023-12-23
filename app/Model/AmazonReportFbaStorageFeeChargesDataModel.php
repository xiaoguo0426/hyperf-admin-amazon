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
 * @property $id
 * @property $merchant_id
 * @property $merchant_store_id
 * @property $month_of_charge
 * @property $asin
 * @property $seller_sku
 * @property $fnsku
 * @property $product_name
 * @property $fulfillment_center
 * @property $country_code
 * @property $longest_side
 * @property $median_side
 * @property $shortest_side
 * @property $measurement_units
 * @property $weight
 * @property $weight_units
 * @property $item_volume
 * @property $volume_units
 * @property $average_quantity_on_hand
 * @property $average_quantity_pending_removal
 * @property $estimated_total_item_volume
 * @property $storage_utilization_ratio
 * @property $storage_utilization_ratio_units
 * @property $base_rate
 * @property $utilization_surcharge_rate
 * @property $currency
 * @property $estimated_monthly_storage_fee
 * @property $total_incentive_fee_amount
 * @property $breakdown_incentive_fee_amount
 * @property $average_quantity_customer_orders
 * @property $dangerous_goods_storage_type
 * @property $product_size_tier
 * @property $eligible_for_inventory_discount
 * @property $qualifies_for_inventory_discount
 * @property $md5_hash
 * @property $created_at
 * @property $updated_at
 */
class AmazonReportFbaStorageFeeChargesDataModel extends Model
{
    protected ?string $table = 'amazon_report_fba_storage_fee_charges_data';

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';

}
