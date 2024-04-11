<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Command\Amazon\FbaInbound;

use AmazonPHP\SellingPartner\AccessToken;
use AmazonPHP\SellingPartner\Exception\ApiException;
use AmazonPHP\SellingPartner\Exception\InvalidArgumentException;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use App\Util\AmazonApp;
use App\Util\AmazonSDK;
use App\Util\ConsoleLog;
use App\Util\Log\AmazonFbaInboundListPlacementOptionsLog;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Context\ApplicationContext;
use Hyperf\Di\Exception\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Input\InputArgument;

#[Command]
class ListTransportationOptions extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('amazon:fba-inbound:list-transportation-options');
    }

    public function configure(): void
    {
        parent::configure();
        $this->addArgument('merchant_id', InputArgument::REQUIRED, '商户id')
            ->addArgument('merchant_store_id', InputArgument::REQUIRED, '店铺id')
            ->addArgument('region', InputArgument::REQUIRED, '地区')
            ->addArgument('inbound_plan_id', InputArgument::REQUIRED, '入站计划id')
            ->setDescription('Amazon Fulfillment Inbound ListTransportationOptions Command');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundException
     * @throws NotFoundExceptionInterface
     * @throws \RedisException
     */
    public function handle(): void
    {
        $merchant_id = (int) $this->input->getArgument('merchant_id');
        $merchant_store_id = (int) $this->input->getArgument('merchant_store_id');
        $region = $this->input->getArgument('region');
        $inbound_plan_id = $this->input->getArgument('inbound_plan_id');

        AmazonApp::tok2($merchant_id, $merchant_store_id, $region, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) use ($inbound_plan_id) {

            $console = ApplicationContext::getContainer()->get(ConsoleLog::class);
            $logger = ApplicationContext::getContainer()->get(AmazonFbaInboundListPlacementOptionsLog::class);

            $page_size = 20;//1-20
            $next_token = null;
            $placement_option_id = null;
            $shipment_id = null;
            var_dump($region);

            $retry = 10;
            while (true) {

                try {

                    $listTransportationOptions = $sdk->fulfillmentInbound()->listTransportationOptions($accessToken, $region, $inbound_plan_id, $page_size, $next_token, $placement_option_id, $shipment_id);
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
            return true;
        });
    }
}
