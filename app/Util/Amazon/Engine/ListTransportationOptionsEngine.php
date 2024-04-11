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
use App\Model\AmazonShipmentModel;
use App\Util\Amazon\Creator\CreatorInterface;
use App\Util\Amazon\Creator\GetShipmentsCreator;
use App\Util\Amazon\Creator\ListPlacementOptionsCreator;
use App\Util\Amazon\Creator\ListTransportationOptionsCreator;
use App\Util\AmazonSDK;
use App\Util\ConsoleLog;
use App\Util\Log\AmazonFbaInboundListPlacementOptionsLog;
use App\Util\Log\AmazonFulfillmentInboundGetShipmentsLog;
use App\Util\RuntimeCalculator;
use Carbon\Carbon;
use Hyperf\Collection\Collection;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\StdoutLoggerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ListTransportationOptionsEngine implements EngineInterface
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
         * @var ListTransportationOptionsCreator $creator
         */
        $inbound_plan_id = $creator->getInboundPlanId();

        $page_size = 20;//1-20
        $next_token = null;

        $retry = 10;
        while (true) {

            try {

                $listTransportationOptions = $sdk->fbaInbound()->listTransportationOptions($accessToken, $region, $inbound_plan_id, $page_size, $next_token, $placement_option_id, $shipment_id);
                $getTransportationOptions = $listTransportationOptions->getTransportationOptions();

                foreach ($getTransportationOptions as $getTransportationOption) {
                    $appointmentSlot = $getTransportationOption->getAppointmentSlot();
                    var_dump($appointmentSlot->getSlotId());
                    var_dump($appointmentSlot->getSlotTime());

                    $carrier = $getTransportationOption->getCarrier();
                    var_dump($carrier->getAlphaCode());
                    var_dump($carrier->getName());

                    $cur_inbound_plan_id = $getTransportationOption->getInboundPlanId();
                    $cur_placement_option_id = $getTransportationOption->getPlacementOptionId();
                    $quote = $getTransportationOption->getQuote();
                    $shipment_id = $getTransportationOption->getShipmentId();
                    $shipping_mode = $getTransportationOption->getShippingMode();
                    $shipping_solution = $getTransportationOption->getShippingSolution();
                    $transportation_option_id = $getTransportationOption->getTransportationOptionId();

                    var_dump($cur_inbound_plan_id);
                    var_dump($cur_placement_option_id);
                    var_dump($quote);
                    var_dump($shipment_id);
                    var_dump($shipping_mode);
                    var_dump($shipping_solution);
                    var_dump($transportation_option_id);

                    var_dump('***********************');
                }

                $pagination = $listTransportationOptions->getPagination();
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
                    $console->warning(sprintf('FbaInbound ListTransportationOptions ApiException Failed. retry:%s merchant_id: %s merchant_store_id: %s region:%s ', $retry, $merchant_id, $merchant_store_id, $region));
                    sleep(10);
                    continue;
                }

                $log = sprintf('FbaInbound ListTransportationOptions ApiException Failed. merchant_id: %s merchant_store_id: %s region:%s', $merchant_id, $merchant_store_id, $region);
                $console->error($log);
                $logger->error($log);
                break;
            } catch (InvalidArgumentException $exception) {
                $log = sprintf('FbaInbound ListTransportationOptions InvalidArgumentException Failed. merchant_id: %s merchant_store_id: %s region:%s', $merchant_id, $merchant_store_id, $region);
                $console->error($log);
                $logger->error($log);
                break;
            }


        }

        $console->notice(sprintf('FulfillmentInbound GetShipments merchant_id:%s merchant_store_id:%s region:%s 完成处理，耗时:%s.', $merchant_id, $merchant_store_id, $region, $runtimeCalculator->stop()));

        return true;
    }
}
