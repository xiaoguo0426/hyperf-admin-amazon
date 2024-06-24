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
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Di\Exception\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Input\InputArgument;

#[Command]
class GetInboundPlan extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('amazon:fba-inbound:get-inbound-plan');
    }

    public function configure(): void
    {
        parent::configure();
        $this->addArgument('merchant_id', InputArgument::REQUIRED, '商户id')
            ->addArgument('merchant_store_id', InputArgument::REQUIRED, '店铺id')
            ->addArgument('region', InputArgument::REQUIRED, '地区')
            ->addArgument('inbound_plan_id', InputArgument::REQUIRED, '入站id')
            ->setDescription('Amazon Fulfillment Inbound GetInboundPlan Command');
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
            try {
                $inboundPlan = $sdk->fulfillmentInbound()->getInboundPlan($accessToken, $region, $inbound_plan_id);
                $contactInformation = $inboundPlan->getContactInformation();
                $inboundPlan->getCreatedAt();
                $inboundPlan->getInboundPlanId();
                $inboundPlan->getLastUpdatedAt();
                $inboundPlan->getMarketplaceIds();
                $inboundPlan->getName();
                $packingOptions = $inboundPlan->getPackingOptions();
                if (! is_null($packingOptions)) {
                    foreach ($packingOptions as $packingOption) {
                        $packingOption->getPackingOptionId();
                        $packingOption->getStatus();
                    }
                }
                $placementOptionSummaries = $inboundPlan->getPlacementOptions();
                if (! is_null($placementOptionSummaries)) {
                    foreach ($placementOptionSummaries as $placementOptionSummary) {
                        $placementOptionSummary->getPlacementOptionId();
                        $placementOptionSummary->getStatus();
                    }
                }
                $shipmentSummaries = $inboundPlan->getShipments();
                if (! is_null($shipmentSummaries)) {
                    foreach ($shipmentSummaries as $shipmentSummary) {
                        $shipment_id = $shipmentSummary->getShipmentId();
                        $shipment_status = $shipmentSummary->getStatus();
                    }
                }
                $address = $inboundPlan->getSourceAddress();
                $address_line1 = $address->getAddressLine1();
                $address_line2 = $address->getAddressLine2();
                $address_city = $address->getCity();
                $address_company_name = $address->getCompanyName();
                $address_country_code = $address->getCountryCode();
                $address_name = $address->getName();
                $address_postal_code = $address->getPostalCode();
                $address_state_or_province_code = $address->getStateOrProvinceCode();
                $status = $inboundPlan->getStatus();

                var_dump($address_line1);
                var_dump($address_line2);
                var_dump($address_city);
                var_dump($address_company_name);
                var_dump($address_country_code);
                var_dump($address_name);
                var_dump($address_postal_code);
                var_dump($address_state_or_province_code);
                var_dump($status);
            } catch (ApiException $exception) {
                //                var_dump($exception->getResponseBody());
                //                var_dump($exception->getMessage());
                //                var_dump($exception->getTraceAsString());
            } catch (InvalidArgumentException $exception) {
            }

            return true;
        });
    }
}
