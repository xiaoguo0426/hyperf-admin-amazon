<?php

declare(strict_types=1);

namespace App\Model;



/**
 * @property int $id 
 * @property int $merchant_id 
 * @property int $merchant_store_id 
 * @property string $region 地区
 * @property string $marketplace_id 市场id(有可能是非标准的marketplace_id)
 * @property string $name 市场名称
 * @property string $country_code 国家二字码
 * @property string $default_currency_code 默认货币
 * @property string $default_language_code 默认语言
 * @property string $domain_name 域名
 * @property int $is_participating 是否参与
 * @property int $has_suspended_listings 是否有停止的Listing
 * @property \Carbon\Carbon $created_at 创建时间
 * @property \Carbon\Carbon $updated_at 更新时间
 */
class AmazonSellerMarketplaceParticipationModel extends Model
{
    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';

    /**
     * The table associated with the model.
     */
    protected ?string $table = 'amazon_seller_marketplace_participation';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'merchant_id' => 'integer', 'merchant_store_id' => 'integer', 'amazon_app_id' => 'integer', 'is_participating' => 'integer', 'has_suspended_listings' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
