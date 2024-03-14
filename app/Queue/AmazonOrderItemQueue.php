<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Queue;

use AmazonPHP\SellingPartner\AccessToken;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use App\Queue\Data\AmazonOrderItemData;
use App\Queue\Data\QueueDataInterface;
use App\Util\Amazon\Creator\OrderItemCreator;
use App\Util\Amazon\Engine\OrderItemEngine;
use App\Util\AmazonApp;
use App\Util\AmazonSDK;
use Hyperf\Di\Exception\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

use function Hyperf\Support\make;

class AmazonOrderItemQueue extends Queue
{
    public function getQueueName(): string
    {
        return 'amazon-order-item';
    }

    public function getQueueDataClass(): string
    {
        return AmazonOrderItemData::class;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundException
     * @throws NotFoundExceptionInterface
     * @throws \RedisException
     */
    public function handleQueueData(QueueDataInterface $queueData): bool
    {
        /**
         * @var AmazonOrderItemData $queueData
         */
        $merchant_id = $queueData->getMerchantId();
        $merchant_store_id = $queueData->getMerchantStoreId();
        $region = $queueData->getRegion();
        $amazon_order_ids = $queueData->getOrderId();

        AmazonApp::tok2($merchant_id, $merchant_store_id, $region, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) use ($amazon_order_ids) {
            $orderItemCreator = new OrderItemCreator();
            $orderItemCreator->setAmazonOrderIds($amazon_order_ids);

            make(OrderItemEngine::class)->launch($amazonSDK, $sdk, $accessToken, $orderItemCreator);

            return true;
        });

        // 更新 amazon_order_items的marketplace_id
        //        \Hyperf\DB\DB::execute('UPDATE amazon_order_items LEFT JOIN amazon_order ON amazon_order_items.merchant_id = amazon_order.merchant_id AND amazon_order_items.merchant_store_id = amazon_order.merchant_store_id AND amazon_order_items.order_id = amazon_order.amazon_order_id SET amazon_order_items.marketplace_id = amazon_order.marketplace_id where amazon_order.merchant_id=? and amazon_order.merchant_store_id=? and amazon_order.region=?;', [$merchant_id, $merchant_store_id, $region]);

        return true;
    }

    public function safetyLine(): int
    {
        return 70;
    }
}
