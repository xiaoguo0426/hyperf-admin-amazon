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
 * Class AmazonAppRegionModel.
 *
 * @property $id
 * @property $merchant_id
 * @property $merchant_store_id
 * @property $amazon_app_id
 * @property $region
 * @property $country_codes
 * @property $refresh_token
 * @property $created_at
 * @property $updated_at
 */
class AmazonAppRegionModel extends Model
{
    protected ?string $table = 'amazon_app_region';

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';
}
