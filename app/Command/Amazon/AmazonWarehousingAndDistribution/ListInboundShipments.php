<?php

namespace App\Command\Amazon\AmazonWarehousingAndDistribution;

use AmazonPHP\SellingPartner\AccessToken;
use AmazonPHP\SellingPartner\Exception\ApiException;
use AmazonPHP\SellingPartner\Exception\InvalidArgumentException;
use AmazonPHP\SellingPartner\Model\DataKiosk\CreateQuerySpecification;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use App\Util\AmazonApp;
use App\Util\AmazonSDK;
use App\Util\Log\AmazonFbaInventoryLog;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\StdoutLoggerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;

class ListInboundShipments extends HyperfCommand
{

    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('amazon:amazon-warehousing-and-distribution:list-inbound-shipments');
    }

    public function configure(): void
    {
        parent::configure();
        $this->addArgument('merchant_id', InputArgument::REQUIRED, '商户id')
            ->addArgument('merchant_store_id', InputArgument::REQUIRED, '店铺id')
            ->addArgument('region', InputArgument::REQUIRED, '地区')
            ->setDescription('Amazon Amazon-Warehousing-And-Distribution ListInboundShipments Command');
    }

    public function handle(): void
    {
        $merchant_id = (int) $this->input->getArgument('merchant_id');
        $merchant_store_id = (int) $this->input->getArgument('merchant_store_id');
        $region = $this->input->getArgument('region');

        AmazonApp::tok2($merchant_id, $merchant_store_id, $region, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) {
            $logger = ApplicationContext::getContainer()->get(AmazonFbaInventoryLog::class);
            $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);

            $retry = 10;

            $sort_by = '';//UPDATED_AT/CREATED_AT
            $sort_order = '';//ASCENDING/DESCENDING
            $shipment_status = '';//https://developer-docs.amazon.com/sp-api/docs/awd_2024-05-09-reference#shipmentstatus
            $updated_after = '';
            $updated_before = '';
            $max_results = '';
            $next_token = null;

            while (true) {
                try {

                    $response = $sdk->warehousingDistribution()->listInboundShipments($accessToken, $region, $sort_by, $sort_order, $shipment_status, $updated_after, $updated_before, $max_results, $next_token);

                    $inboundShipmentSummaryList = $response->getShipments();
                    foreach ($inboundShipmentSummaryList as $inboundShipmentSummary) {
                        $inboundShipmentSummary->getCreatedAt();
                        $inboundShipmentSummary->getExternalReferenceId();
                        $inboundShipmentSummary->getOrderId();
                        $inboundShipmentSummary->getShipmentId();
                        $inboundShipmentSummary->getShipmentStatus();
                        $inboundShipmentSummary->getUpdatedAt();
                    }

                    $next_token = $response->getNextToken();
                    if (is_null($next_token)) {
                        break;
                    }

                } catch (ApiException $exception) {
                    --$retry;
                    if ($retry > 0) {
                        $console->warning(sprintf('ApiException Inventory API retry:%s Exception:%s', $retry, $exception->getMessage()));
                        sleep(10);
                        continue;
                    }
                    $console->error('ApiException DataKiosk CreateQuery API 重试次数耗尽', [
                        'message' => $exception->getMessage(),
                        'trace' => $exception->getTraceAsString(),
                    ]);

                    $logger->error('ApiException DataKiosk CreateQuery API 重试次数耗尽', [
                        'message' => $exception->getMessage(),
                        'trace' => $exception->getTraceAsString(),
                    ]);

                } catch (InvalidArgumentException $exception) {
                    $console->error('InvalidArgumentException DataKiosk CreateQuery API请求错误', [
                        'message' => $exception->getMessage(),
                        'trace' => $exception->getTraceAsString(),
                    ]);

                    $logger->error('InvalidArgumentException DataKiosk CreateQuery API请求错误', [
                        'message' => $exception->getMessage(),
                        'trace' => $exception->getTraceAsString(),
                    ]);
                    break;
                }
            }
            return true;
        });

    }

}