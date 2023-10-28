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
 * @property array $config
 * @property string $status
 * @property string $created_at
 * @property string $updated_at
 */
class AmazonAppModel extends Model
{
    protected ?string $table = 'amazon_app';

    /**
     * @throws \JsonException
     */
    public function getConfigAttribute(string $value): array
    {
        //        $data = [];
        //        $decodes = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        //        foreach ($decodes as $region => $json) {
        //            $data[$region] = new RegionRefreshTokenConfig($json['region'], $json['country_ids'], $json['refresh_token']);
        //        }
        //        return $data;
        return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws \JsonException
     */
    public function setConfigAttribute(array $configs): void
    {
        $this->attributes['config'] = json_encode($configs, JSON_THROW_ON_ERROR);
    }

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
