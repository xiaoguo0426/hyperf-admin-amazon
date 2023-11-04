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
use App\Queue\Data\AmazonFinanceListFinancialEventsByGroupIdData;
use App\Queue\Data\QueueDataInterface;
use App\Util\Amazon\Creator\ListFinancialEventsByGroupIdCreator;
use App\Util\Amazon\Engine\ListFinancialEventsByGroupIdEngine;
use App\Util\AmazonApp;
use App\Util\AmazonSDK;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class AmazonFinanceFinancialListEventsByGroupIdQueue extends Queue
{
    public function getQueueName(): string
    {
        return 'amazon-financial-list-events-by-group-id';
    }

    public function getQueueDataClass(): string
    {
        return AmazonFinanceListFinancialEventsByGroupIdData::class;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function handleQueueData(QueueDataInterface $queueData): bool
    {
        /**
         * @var AmazonFinanceListFinancialEventsByGroupIdData $queueData
         */
        $merchant_id = $queueData->getMerchantId();
        $merchant_store_id = $queueData->getMerchantStoreId();
        $financial_event_group_id = $queueData->getFinancialEventGroupId();

        AmazonApp::tok($merchant_id, $merchant_store_id, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) use ($financial_event_group_id) {
            $creator = new ListFinancialEventsByGroupIdCreator();
            $creator->setGroupId($financial_event_group_id);
            $creator->setMaxResultsPerPage(100);

            \Hyperf\Support\make(ListFinancialEventsByGroupIdEngine::class)->launch($amazonSDK, $sdk, $accessToken, $creator);

            return true;
        });

        return true;
    }

    public function safetyLine(): int
    {
        return 70;
    }
}
