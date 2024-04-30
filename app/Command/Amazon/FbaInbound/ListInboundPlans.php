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
class ListInboundPlans extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('amazon:fba-inbound:list-inbound-plans');
    }

    public function configure(): void
    {
        parent::configure();
        $this->addArgument('merchant_id', InputArgument::REQUIRED, '商户id')
            ->addArgument('merchant_store_id', InputArgument::REQUIRED, '店铺id')
            ->addArgument('region', InputArgument::REQUIRED, '地区')
            ->setDescription('Amazon Fulfillment Inbound listInboundPlans Command');
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

        AmazonApp::tok2($merchant_id, $merchant_store_id, $region, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) {
            $page_size = 30;
            $pagination_token = null;
            $status = null; // https://developer-docs.amazon.com/sp-api/docs/fulfillment-inbound-api-v2024-03-20-reference#status
            $sort_by = null; // https://developer-docs.amazon.com/sp-api/docs/fulfillment-inbound-api-v2024-03-20-reference#sortby
            $sort_order = null; // https://developer-docs.amazon.com/sp-api/docs/fulfillment-inbound-api-v2024-03-20-reference#sortorder
            var_dump($region);
            while (true) {
                $listInboundPlansResponse = $sdk->fulfillmentInbound()->listInboundPlans($accessToken, $region, $page_size, $pagination_token, $status, $sort_by, $sort_order);

                $inboundPlanSummary = $listInboundPlansResponse->getInboundPlans();
                if (is_null($inboundPlanSummary)) {
                    break;
                }
                foreach ($inboundPlanSummary as $inboundPlanSummaryItem) {
                    $contactInformation = $inboundPlanSummaryItem->getContactInformation();
                    $contact_information_email = $contactInformation->getEmail() ?? '';
                    $contact_information_name = $contactInformation->getName() ?? '';

                    $created_at = $inboundPlanSummaryItem->getCreatedAt()->format('Y-m-d H:i:s');
                    $inbound_plan_id = $inboundPlanSummaryItem->getInboundPlanId();
                    $last_updated_at = $inboundPlanSummaryItem->getLastUpdatedAt()->format('Y-m-d H:i:s');
                    $marketplace_ids = $inboundPlanSummaryItem->getMarketplaceIds();
                    $name = $inboundPlanSummaryItem->getName();
                    $address = $inboundPlanSummaryItem->getSourceAddress();
                    $address_line1 = $address->getAddressLine1();
                    $address_line2 = $address->getAddressLine2();
                    $address_city = $address->getCity();
                    $address_company_name = $address->getCompanyName();
                    $address_country_code = $address->getCountryCode();
                    $address_name = $address->getName();
                    $address_postal_code = $address->getPostalCode();
                    $address_state_or_province_code = $address->getStateOrProvinceCode();
                    $status = $inboundPlanSummaryItem->getStatus();

                    var_dump($contact_information_email);
                    var_dump($contact_information_name);
                    var_dump($created_at);
                    var_dump($inbound_plan_id);
                    var_dump($last_updated_at);
                    var_dump($marketplace_ids);
                    var_dump($name);
                    var_dump($address_line1);
                    var_dump($address_line2);
                    var_dump($address_city);
                    var_dump($address_company_name);
                    var_dump($address_country_code);
                    var_dump($address_name);
                    var_dump($address_postal_code);
                    var_dump($address_state_or_province_code);
                    var_dump($status);
                    var_dump('************************');
                }

                $pagination = $listInboundPlansResponse->getPagination();
                if (is_null($pagination)) {
                    break;
                }
                $pagination_token = $pagination->getNextToken();
                if (is_null($pagination_token)) {
                    break;
                }
            }
            return true;
        });
    }
}
