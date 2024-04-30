<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Util\Amazon\Engine;

use AmazonPHP\SellingPartner\AccessToken;
use AmazonPHP\SellingPartner\Exception\ApiException;
use AmazonPHP\SellingPartner\Exception\InvalidArgumentException;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use App\Util\Amazon\Creator\CreatorInterface;
use App\Util\Amazon\Creator\ListPlacementOptionsCreator;
use App\Util\AmazonSDK;
use App\Util\ConsoleLog;
use App\Util\Log\AmazonFbaInboundListPlacementOptionsLog;
use App\Util\RuntimeCalculator;
use Hyperf\Context\ApplicationContext;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ListPlacementOptionsEngine implements EngineInterface
{
    /**
     * @throws ContainerExceptionInterface
     * @throws \JsonException
     * @throws NotFoundExceptionInterface
     * @throws \Exception
     */
    public function launch(AmazonSDK $amazonSDK, SellingPartnerSDK $sdk, AccessToken $accessToken, CreatorInterface $creator): bool
    {
        $console = ApplicationContext::getContainer()->get(ConsoleLog::class);
        $logger = ApplicationContext::getContainer()->get(AmazonFbaInboundListPlacementOptionsLog::class);

        $region = $amazonSDK->getRegion();

        $merchant_id = $amazonSDK->getMerchantId();
        $merchant_store_id = $amazonSDK->getMerchantStoreId();

        $runtimeCalculator = new RuntimeCalculator();
        $runtimeCalculator->start();

        /**
         * @var ListPlacementOptionsCreator $creator
         */
        $inbound_plan_id = $creator->getInboundPlanId();

        $page_size = 20; // 1-20
        $next_token = null;

        $retry = 10;
        while (true) {
            try {
                $listPlacementOptionsResponse = $sdk->fulfillmentInbound()->listPlacementOptions($accessToken, $region, $inbound_plan_id, $page_size, $next_token);
                $placementOptions = $listPlacementOptionsResponse->getPlacementOptions();
                foreach ($placementOptions as $placementOption) {
                    $discounts = $placementOption->getDiscounts();
                    foreach ($discounts as $discount) {
                        $discount->getDescription();
                        $discount->getTarget();
                        $discount->getType();
                        $discount->getValue();
                    }
                    $expiration = $placementOption->getExpiration();
                    if (! is_null($expiration)) {
                        var_dump($expiration->format('Y-m-d H:i:s'));
                    }
                    $fees = $placementOption->getFees();
                    foreach ($fees as $fee) {
                        $fee->getDescription();
                        $fee->getTarget();
                        $fee->getType();
                        $fee->getValue();
                    }

                    $placement_option_id = $placementOption->getPlacementOptionId();
                    $shipment_ids = $placementOption->getShipmentIds();
                    $status = $placementOption->getStatus();
                    var_dump($placement_option_id);
                    var_dump($shipment_ids);
                    var_dump($status);
                }

                $pagination = $listPlacementOptionsResponse->getPagination();
                if (is_null($pagination)) {
                    break;
                }
                $next_token = $pagination->getNextToken();
                if (is_null($next_token)) {
                    break;
                }
            } catch (ApiException $exception) {
                var_dump($exception->getResponseBody());
                --$retry;
                if ($retry > 0) {
                    $console->warning(sprintf('FbaInbound ListPlacementOptions ApiException Failed. retry:%s merchant_id: %s merchant_store_id: %s region:%s ', $retry, $merchant_id, $merchant_store_id, $region));
                    sleep(10);
                    continue;
                }

                $log = sprintf('FbaInbound ListPlacementOptions ApiException Failed. merchant_id: %s merchant_store_id: %s region:%s', $merchant_id, $merchant_store_id, $region);
                $console->error($log);
                $logger->error($log);
                break;
            } catch (InvalidArgumentException $exception) {
                $log = sprintf('FbaInbound ListPlacementOptions InvalidArgumentException Failed. merchant_id: %s merchant_store_id: %s region:%s', $merchant_id, $merchant_store_id, $region);
                $console->error($log);
                $logger->error($log);
                break;
            }
        }

        $console->notice(sprintf('FulfillmentInbound GetShipments merchant_id:%s merchant_store_id:%s region:%s 完成处理，耗时:%s.', $merchant_id, $merchant_store_id, $region, $runtimeCalculator->stop()));

        return true;
    }
}
