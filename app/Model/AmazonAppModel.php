<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Model;

use App\Util\RegionRefreshTokenConfig;

/**
 * Class AmazonAppModel.
 * @property int $id
 * @property int $merchant_id
 * @property int $merchant_store_id
 * @property string $seller_id
 * @property string $app_id
 * @property string $app_name
 * @property string $aws_access_key
 * @property string $aws_secret_key
 * @property string $user_arn
 * @property string $role_arn
 * @property string $lwa_client_id
 * @property string $lwa_client_id_secret
 * @property string $region
 * @property string $country_ids
 * @property string $refresh_token
 * @property string $status
 * @property string $created_at
 * @property string $updated_at
 */
class AmazonAppModel extends Model
{
    protected ?string $table = 'amazon_app';

    /**
     * @return RegionRefreshTokenConfig[]
     */
    public function getRegionRefreshTokenConfigs(): array
    {
        $configs = [];
        foreach ($this->config as $region => $data) {
            if ($data['region'] && $data['country_ids'] && $data['refresh_token']) {
                $configs[$region] = new RegionRefreshTokenConfig($data['region'], $data['country_ids'], $data['refresh_token']);
            }
        }
        return $configs;
    }
}
