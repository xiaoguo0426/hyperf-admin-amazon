<?php

namespace App\Util\Amazon\Engine;

use AmazonPHP\SellingPartner\AccessToken;
use AmazonPHP\SellingPartner\Exception\ApiException;
use AmazonPHP\SellingPartner\Exception\InvalidArgumentException;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use App\Util\Amazon\Creator\CreatorInterface;
use App\Util\Amazon\Creator\GetShipmentItemsCreator;
use App\Util\AmazonSDK;
use App\Util\Log\AmazonFulfillmentInboundGetShipmentItemsLog;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\StdoutLoggerInterface;

class GetShipmentItemsEngine implements EngineInterface
{

    public function launch(AmazonSDK $amazonSDK, SellingPartnerSDK $sdk, AccessToken $accessToken, CreatorInterface $creator): bool
    {
        $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
        $logger = ApplicationContext::getContainer()->get(AmazonFulfillmentInboundGetShipmentItemsLog::class);

        /**
         * @var GetShipmentItemsCreator $creator
         */
        $query_type = $creator->getQueryType();
        $marketplace_id = $creator->getMarketplaceId();
        $last_updated_after = $creator->getLastUpdatedAfter();
        $last_updated_before = $creator->getLastUpdatedBefore();

        $region = $amazonSDK->getRegion();

        $merchant_id = $amazonSDK->getMerchantId();
        $merchant_store_id = $amazonSDK->getMerchantStoreId();

        $next_token = null;

        $console->info(sprintf('FulfillmentInbound merchant_id:%s merchant_store_id:%s region:%s 开始处理.', $merchant_id, $merchant_store_id, $region));

        $retry = 10;
        while (true) {
            try {
                $response = $sdk->fulfillmentInbound()->getShipmentItems($accessToken, $region, $query_type, $marketplace_id, $last_updated_after, $last_updated_before, $next_token);

                $payload = $response->getPayload();
                if ($payload === null) {
                    break;
                }


                $retry = 10;
            } catch (ApiException $e) {
                --$retry;
                if ($retry > 0) {
                    $console->warning(sprintf('FulfillmentInbound ApiException GetShipments Failed. retry:%s merchant_id: %s merchant_store_id: %s region:%s ', $retry, $merchant_id, $merchant_store_id, $region));
                    sleep(10);
                    continue;
                }

                $log = sprintf('FulfillmentInbound ApiException GetShipments Failed. merchant_id: %s merchant_store_id: %s region:%s', $merchant_id, $merchant_store_id, $region);
                $console->error($log);
                $logger->error($log);
                break;
            } catch (InvalidArgumentException $e) {
                $log = sprintf('FulfillmentInbound InvalidArgumentException GetShipments Failed. merchant_id: %s merchant_store_id: %s region:%s', $merchant_id, $merchant_store_id, $region);
                $console->error($log);
                $logger->error($log);
                break;
            }
        }

        return true;
    }
}