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
use App\Util\Log\AmazonFulfillmentInboundGetLabelsLog;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Di\Exception\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Input\InputArgument;

#[Command]
class GetBillOfLading extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('amazon:fulfillment-inbound:get-bill-of-lading');
    }

    public function configure(): void
    {
        parent::configure();
        // 指令配置
        $this->addArgument('merchant_id', InputArgument::REQUIRED, '商户id')
            ->addArgument('merchant_store_id', InputArgument::REQUIRED, '店铺id')
            ->addArgument('region', InputArgument::REQUIRED, '地区')
            ->addArgument('shipment_id', InputArgument::REQUIRED, '货件id')
            ->setDescription('Amazon Fulfillment Inbound Get Bill Of Lading Command');
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
        $shipment_id = $this->input->getArgument('shipment_id');
        return;
        //该API已废弃
        AmazonApp::tok2($merchant_id, $merchant_store_id, $region, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) use ($shipment_id) {
            $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
            $logger = ApplicationContext::getContainer()->get(AmazonFulfillmentInboundGetLabelsLog::class);

            $amazonShipmentsCollections = AmazonShipmentModel::query()
                ->where('merchant_id', $merchant_id)
                ->where('merchant_store_id', $merchant_store_id)
                ->where('region', $region)
                ->when($shipment_id, function ($query, $value) {
                    return $query->where('shipment_id', $value);
                })
                ->orderByDesc('id')
                ->get();
            if ($amazonShipmentsCollections->isEmpty()) {
                return true;
            }

            foreach ($amazonShipmentsCollections as $amazonShipmentsCollection) {
                $shipment_id = $amazonShipmentsCollection->shipment_id;
                try {
                    $response = $sdk->fulfillmentInbound()->getBillOfLading($accessToken, $region, $shipment_id);

                    $errorList = $response->getErrors();
                    if (! is_null($errorList)) {
                        foreach ($errorList as $error) {
                            $code = $error->getCode();
                            $msg = $error->getMessage();
                            $detail = $error->getDetails();

                            $log = sprintf('FulfillmentInbound InvalidArgumentException GetBillOfLading Failed. code:%s msg:%s detail:%s merchant_id: %s merchant_store_id: %s ', $code, $msg, $detail, $merchant_id, $merchant_store_id);
                            $console->error($log);
                            $logger->error($log);
                        }
                        break;
                    }

                    $billOfLadingDownloadURL = $response->getPayload();
                    if (is_null($billOfLadingDownloadURL)) {
                        $console->warning(sprintf('merchant_id:%s merchant_store_id:%s shipment_id:%s API响应 payload为空', $merchant_id, $merchant_store_id, $shipment_id));
                        continue;
                    }
                    // download_url只有15秒的有效期
                    $download_url = $billOfLadingDownloadURL->getDownloadUrl();

                    $console->info(sprintf('merchant_id:%s merchant_store_id:%s shipment_id:%s url:%s', $merchant_id, $merchant_store_id, $shipment_id, $download_url));
                } catch (ApiException $exception) {
                    $console->error(sprintf('merchant_id:%s merchant_store_id:%s shipment_id:%s %s', $merchant_id, $merchant_store_id, $shipment_id, $exception->getMessage()));
                } catch (InvalidArgumentException $exception) {
                    $console->error('InvalidArgumentException API请求错误', [
                        'message' => $exception->getMessage(),
                        'trace' => $exception->getTraceAsString(),
                    ]);

                    $logger->error('InvalidArgumentException API请求错误', [
                        'message' => $exception->getMessage(),
                        'trace' => $exception->getTraceAsString(),
                    ]);
                }
            }
            return true;
        });
    }
}
