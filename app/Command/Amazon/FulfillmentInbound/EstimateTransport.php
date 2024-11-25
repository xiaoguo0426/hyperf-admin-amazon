<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Command\Amazon\FulfillmentInbound;

use AmazonPHP\SellingPartner\AccessToken;
use AmazonPHP\SellingPartner\Exception\ApiException;
use AmazonPHP\SellingPartner\Exception\InvalidArgumentException;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use App\Model\AmazonShipmentModel;
use App\Util\AmazonApp;
use App\Util\AmazonSDK;
use App\Util\ConsoleLog;
use App\Util\Log\AmazonFulfillmentInboundGetTransportDetailsLog;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Context\ApplicationContext;
use Hyperf\Database\Model\ModelNotFoundException;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;

#[Command]
class EstimateTransport extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('amazon:fulfillment-inbound:estimate-transport');
    }

    public function configure(): void
    {
        parent::configure();
        $this->setDescription('Amazon Fulfillment Inbound Get Inbound Guidance Command');
        $this->addArgument('merchant_id', InputArgument::REQUIRED, '商户id')
            ->addArgument('merchant_store_id', InputArgument::REQUIRED, '店铺id')
            ->addArgument('region', InputArgument::REQUIRED, '地区')
            ->addArgument('shipment_id', InputArgument::REQUIRED, '货件ID')
            ->setDescription('Amazon Fulfillment Inbound EstimateTransport Command');
    }

    public function handle(): void
    {
        $merchant_id = (int) $this->input->getArgument('merchant_id');
        $merchant_store_id = (int) $this->input->getArgument('merchant_store_id');
        $region = $this->input->getArgument('region');
        $shipment_id = $this->input->getArgument('shipment_id');
        return;
        //该API已废弃
        AmazonApp::tok2($merchant_id, $merchant_store_id, $region, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) use ($shipment_id) {
            $console = ApplicationContext::getContainer()->get(ConsoleLog::class);
            $logger = ApplicationContext::getContainer()->get(AmazonFulfillmentInboundGetTransportDetailsLog::class);

            try {
                $amazonShipmentsCollection = AmazonShipmentModel::query()
                    ->where('merchant_id', $merchant_id)
                    ->where('merchant_store_id', $merchant_store_id)
                    ->where('region', $region)
                    ->where('shipment_id', $shipment_id)
                    ->firstOrFail();
            } catch (ModelNotFoundException) {
                $console->error(sprintf('merchant_id:%s merchant_store_id:%s shipment_id:%s 不存在', $merchant_id, $merchant_store_id, $shipment_id));
                return true;
            }

            $retry = 10;
            while (true) {
                try {
                    $estimateTransportResponse = $sdk->fulfillmentInbound()->estimateTransport($accessToken, $region, $shipment_id);
                    $payload = $estimateTransportResponse->getPayload();
                    if (is_null($payload)) {
                        $console->warning(sprintf('merchant_id:%s merchant_store_id:%s shipment_id:%s API响应 payload为空', $merchant_id, $merchant_store_id, $shipment_id));
                        continue;
                    }
                    $errorList = $estimateTransportResponse->getErrors();
                    $errors = [];
                    if (! is_null($errorList)) {
                        foreach ($errorList as $error) {
                            $code = $error->getCode();
                            $message = $error->getMessage();
                            $details = $error->getDetails();
                            $errors[] = [
                                'code' => $code,
                                'message' => $message,
                                'details' => $details,
                            ];
                        }
                        $console->error(sprintf('merchant_id:%s merchant_store_id:%s shipment_id:%s errors:%s', $merchant_id, $merchant_store_id, $shipment_id, json_encode($errors, JSON_THROW_ON_ERROR)));
                        continue;
                    }

                    $transportResult = $payload->getTransportResult();
                    if (is_null($transportResult)) {
                        $console->warning(sprintf('merchant_id:%s merchant_store_id:%s shipment_id:%s TransportResult is null', $merchant_id, $merchant_store_id, $shipment_id));
                        continue;
                    }

                    $transport_status = $transportResult->getTransportStatus()->toString();

                    $error_code = $transportResult->getErrorCode();
                    $error_description = $transportResult->getErrorDescription();
                    var_dump($transport_status);
                    var_dump($error_code);
                    var_dump($error_description);

                    break;
                } catch (ApiException $exception) {
                    var_dump($exception->getResponseBody());
                    --$retry;
                    if ($retry > 0) {
                        $console->warning(sprintf('FulfillmentInbound ApiException EstimateTransport Failed. retry:%s merchant_id: %s merchant_store_id: %s region:%s ', $retry, $merchant_id, $merchant_store_id, $region));
                        sleep(10);
                        continue;
                    }

                    $log = sprintf('FulfillmentInbound ApiException EstimateTransport Failed. merchant_id: %s merchant_store_id: %s region:%s', $merchant_id, $merchant_store_id, $region);
                    $console->error($log);
                    $logger->error($log);
                    break;
                } catch (InvalidArgumentException $exception) {
                    $log = sprintf('FulfillmentInbound EstimateTransport InvalidArgumentException Failed. merchant_id: %s merchant_store_id: %s region:%s', $merchant_id, $merchant_store_id, $region);
                    $console->error($log);
                    $logger->error($log);
                    break;
                }
            }

            return true;
        });
    }
}
