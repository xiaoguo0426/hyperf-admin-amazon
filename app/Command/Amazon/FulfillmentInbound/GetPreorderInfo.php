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
use AmazonPHP\SellingPartner\Marketplace;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use App\Model\AmazonShipmentModel;
use App\Util\AmazonApp;
use App\Util\AmazonSDK;
use App\Util\Log\AmazonFulfillmentInboundGetPreorderInfoLog;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Database\Model\ModelNotFoundException;
use Hyperf\Di\Exception\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RedisException;
use Symfony\Component\Console\Input\InputArgument;

#[Command]
class GetPreorderInfo extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('amazon:fulfillment-inbound:get-preorder-info');
    }

    public function configure(): void
    {
        parent::configure();
        // 指令配置
        $this->addArgument('merchant_id', InputArgument::REQUIRED, '商户id')
            ->addArgument('merchant_store_id', InputArgument::REQUIRED, '店铺id')
            ->addArgument('marketplace_id', InputArgument::REQUIRED, '市场id')
            ->addArgument('shipment_id', InputArgument::REQUIRED, '货件id')
            ->setDescription('Amazon Fulfillment Inbound GetPreorderInfo Command');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     * @return void
     */
    public function handle(): void
    {
        $merchant_id = (int) $this->input->getArgument('merchant_id');
        $merchant_store_id = (int) $this->input->getArgument('merchant_store_id');
        $marketplace_id = $this->input->getArgument('marketplace_id');
        $shipment_id = $this->input->getArgument('shipment_id');

        $region = Marketplace::fromId($marketplace_id)->region();

        AmazonApp::tok2($merchant_id, $merchant_store_id, $region, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) use ($shipment_id) {
            $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
            $logger = ApplicationContext::getContainer()->get(AmazonFulfillmentInboundGetPreorderInfoLog::class);

            try {
                $amazonShipmentsCollections = AmazonShipmentModel::query()
                    ->where('merchant_id', $merchant_id)
                    ->where('merchant_store_id', $merchant_store_id)
                    ->where('region', $region)
                    ->where('shipment_id', $shipment_id)
                    ->firstOrFail();
            } catch (ModelNotFoundException) {
                $console->error(sprintf('merchant_id:%s merchant_store_id:%s region:%s shipment_id:%s 数据不存在，请检查', $merchant_id, $merchant_store_id, $region, $shipment_id));
                return true;
            }
            $retry = 10;
            while (true) {
                try {
                    $getPreorderInfoResponse = $sdk->fulfillmentInbound()->getPreorderInfo($accessToken, $region, $shipment_id, implode(',', $marketplace_ids));
                    $payload = $getPreorderInfoResponse->getPayload();
                    if (is_null($payload)) {
                        $console->warning(sprintf('merchant_id:%s merchant_store_id:%s shipment_id:%s API响应 payload为空', $merchant_id, $merchant_store_id, $shipment_id));
                        continue;
                    }
                    $errorList = $getPreorderInfoResponse->getErrors();
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

                    $shipment_contains_preorderable_items = $payload->getShipmentContainsPreorderableItems() ?? false;
                    $shipment_confirmed_for_preorder = $payload->getShipmentConfirmedForPreorder() ?? false;
                    $needByDate = $payload->getNeedByDate();
                    $need_by_date = '';
                    if (! is_null($needByDate)) {
                        $need_by_date = $needByDate->format('Y-m-d H:i:s');
                    }
                    $confirmedFulfillableDate = $payload->getConfirmedFulfillableDate();
                    $confirmed_fulfillable_date = '';
                    if (! is_null($confirmedFulfillableDate)) {
                        $confirmed_fulfillable_date = $confirmedFulfillableDate->format('Y-m-d H:i:s');
                    }
                    var_dump($shipment_contains_preorderable_items);
                    var_dump($shipment_confirmed_for_preorder);
                    var_dump($need_by_date);
                    var_dump($confirmed_fulfillable_date);
                    break;
                } catch (ApiException $exception) {
                    --$retry;
                    if ($retry > 0) {
                        $console->warning(sprintf('merchant_id:%s merchant_store_id:%s 第 %s 次重试', $merchant_id, $merchant_store_id, $retry));
                        sleep(3);
                        continue;
                    }

                    $console->error(sprintf('merchant_id:%s merchant_store_id:%s region:%s shipment_id:%s %s', $merchant_id, $merchant_store_id, $region, $shipment_id, $exception->getMessage()));
                    break;
                } catch (InvalidArgumentException $exception) {
                    $console->error('InvalidArgumentException API请求错误', [
                        'message' => $exception->getMessage(),
                        'trace' => $exception->getTraceAsString(),
                    ]);

                    $logger->error('InvalidArgumentException API请求错误', [
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
