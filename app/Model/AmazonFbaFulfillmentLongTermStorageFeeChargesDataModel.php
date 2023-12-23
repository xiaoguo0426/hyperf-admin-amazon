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
 * @property $snapshot_date
 * @property $sku
 * @property $fnsku
 * @property $asin
 * @property $product_name
 * @property $condition
 * @property $per_unit_volume
 * @property $currency
 * @property $volume_unit
 * @property $country
 * @property $qty_charged
 * @property $amount_charged
 * @property $surcharge_age_tier
 * @property $rate_surcharge
 * @property $created_at
 * @property $updated_at
 */
class AmazonFbaFulfillmentLongTermStorageFeeChargesDataModel extends Model
{
    protected ?string $table = 'amazon_report_fba_fulfillment_longterm_storage_fee_charges_data';

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';
}
