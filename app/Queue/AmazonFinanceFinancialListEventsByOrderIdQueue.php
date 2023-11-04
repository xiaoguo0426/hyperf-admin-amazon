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
use App\Queue\Data\AmazonFinanceListFinancialEventsByOrderIdData;
use App\Queue\Data\QueueDataInterface;
use App\Util\Amazon\Creator\ListFinancialEventsByOrderIdCreator;
use App\Util\Amazon\Engine\ListFinancialEventsByOrderIdEngine;
use App\Util\AmazonApp;
use App\Util\AmazonSDK;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class AmazonFinanceFinancialListEventsByOrderIdQueue extends Queue
{
    public function getQueueName(): string
    {
        return 'amazon-financial-list-events-by-order-id';
    }

    public function getQueueDataClass(): string
    {
        return AmazonFinanceListFinancialEventsByOrderIdData::class;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function handleQueueData(QueueDataInterface $queueData): bool
    {
        /**
         * @var AmazonFinanceListFinancialEventsByOrderIdData $queueData
         */
        $merchant_id = $queueData->getMerchantId();
        $merchant_store_id = $queueData->getMerchantStoreId();
        $order_id = $queueData->getOrderId();

        AmazonApp::tok($merchant_id, $merchant_store_id, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) use ($order_id) {
            $creator = new ListFinancialEventsByOrderIdCreator();
            $creator->setOrderId($order_id);
            $creator->setMaxResultsPerPage(100);

            \Hyperf\Support\make(ListFinancialEventsByOrderIdEngine::class)->launch($amazonSDK, $sdk, $accessToken, $creator);

            return true;
        });

        return true;
    }

    public function safetyLine(): int
    {
        return 70;
    }
}
