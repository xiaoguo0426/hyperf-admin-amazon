<?php

namespace App\Command\Crontab\Amazon;

use App\Model\AmazonAppModel;
use App\Model\AmazonInventoryModel;
use App\Util\AmazonApp;
use App\Util\RedisHash\AmazonInventoryFnSkuToSkuMapHash;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use JsonException;
use Psr\Container\ContainerInterface;
use RedisException;
use function Hyperf\Support\make;

#[Command]
class CommonCacheRefresh extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('crontab:amazon:common-cache-refresh');
        // 指令配置
        $this->setDescription('Crontab Amazon Common Cache Refresh Command');
    }

    public function handle(): void
    {
        AmazonApp::single(static function (AmazonAppModel $amazonAppCollection) {
            $merchant_id = $amazonAppCollection->merchant_id;
            $merchant_store_id = $amazonAppCollection->merchant_store_id;

            /**
             * @var AmazonInventoryFnSkuToSkuMapHash $hash
             */
            $hash = make(AmazonInventoryFnSkuToSkuMapHash::class, [$merchant_id, $merchant_store_id]);

            $fnSkuMapCollections = AmazonInventoryModel::query()
                ->where('merchant_id', $merchant_id)
                ->where('merchant_store_id', 1)
                ->pluck('seller_sku', 'fn_sku');

            if ($fnSkuMapCollections->isEmpty()) {
                return true;
            }
            foreach ($fnSkuMapCollections as $fn_sku => $seller_sku) {
                try {
                    $hash->setSellerSkuByFnSku($fn_sku, $seller_sku);
                } catch (JsonException|RedisException $e) {
                }
            }

            return true;
        });
    }
}