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
use App\Model\AmazonShipmentItemsModel;
use App\Model\AmazonShipmentModel;
use App\Util\AmazonApp;
use App\Util\AmazonSDK;
use App\Util\ConsoleLog;
use App\Util\Log\AmazonFulfillmentInboundGetShipmentItemsByShipmentIdLog;
use App\Util\RuntimeCalculator;
use Carbon\Carbon;
use Hyperf\Collection\Collection;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Context\ApplicationContext;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Input\InputArgument;

#[Command]
class GetShipmentItemsByShipmentId extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('amazon:fulfillment-inbound:get-shipment-items-by-shipment-id');
    }

    public function configure(): void
    {
        parent::configure();
        $this->addArgument('merchant_id', InputArgument::REQUIRED, '商户id')
            ->addArgument('merchant_store_id', InputArgument::REQUIRED, '店铺id')
            ->addArgument('shipment_id', InputArgument::OPTIONAL, '货件ID', null)
            ->setDescription('Amazon Fulfillment Inbound Get Shipment Items By Shipment Id Command');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function handle(): void
    {
        $merchant_id = (int) $this->input->getArgument('merchant_id');
        $merchant_store_id = (int) $this->input->getArgument('merchant_store_id');
        $shipment_id = $this->input->getArgument('shipment_id');

        AmazonApp::tok($merchant_id, $merchant_store_id, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) use ($shipment_id) {
            $console = ApplicationContext::getContainer()->get(ConsoleLog::class);
            $logger = ApplicationContext::getContainer()->get(AmazonFulfillmentInboundGetShipmentItemsByShipmentIdLog::class);

            $amazonShipmentCollections = AmazonShipmentModel::query()
                ->where('merchant_id', $merchant_id)
                ->where('merchant_store_id', $merchant_store_id)
                ->when($shipment_id, function ($query, $value) {
                    return $query->where('shipment_id', $value);
                })
                ->orderByDesc('id')
                ->get();
            if ($amazonShipmentCollections->isEmpty()) {
                if (is_null($shipment_id)) {
                    $console->error(sprintf('merchant_id:%s merchant_store_id:%s 没有符合条件的shipment数据', $merchant_id, $merchant_store_id));
                } else {
                    $console->error(sprintf('merchant_id:%s merchant_store_id:%s shipment_id:%s 不存在', $merchant_id, $merchant_store_id, $shipment_id));
                }
                return true;
            }

            $retry = 10;
            foreach ($amazonShipmentCollections as $amazonShipmentCollection) {
                $runtimeCalculator = new RuntimeCalculator();
                $runtimeCalculator->start();

                $shipment_id = $amazonShipmentCollection->shipment_id;
                $seller_skus = [];

                $console->info(sprintf('FulfillmentInbound getShipmentItemsByShipmentId shipment_id:%s merchant_id:%s merchant_store_id:%s 开始处理.', $shipment_id, $merchant_id, $merchant_store_id));

                $collections = new Collection();

                $now = Carbon::now()->format('Y-m-d H:i:s');
                //                var_dump($amazonShipmentCollection->shipment_id);
                //                var_dump($amazonShipmentCollection->country_id);
                //                $marketplace_id = $amazonSDK->fetchMarketplaceIdFromCountryId($amazonShipmentCollection->country_id);

                while (true) {
                    try {
                        $response = $sdk->fulfillmentInbound()->getShipmentItemsByShipmentId($accessToken, $region, $shipment_id, '');
                        $payload = $response->getPayload();
                        if ($payload === null) {
                            break;
                        }
                        $itemData = $payload->getItemData();
                        if (is_null($itemData)) {
                            break;
                        }

                        foreach ($itemData as $item) {
                            $shipment_id = $item->getShipmentId();
                            $seller_sku = $item->getSellerSku();
                            $fulfillment_network_sku = $item->getFulfillmentNetworkSku();
                            $quantity_shipped = $item->getQuantityShipped();
                            $quantity_received = $item->getQuantityReceived();
                            $quantity_in_case = $item->getQuantityInCase();
                            $releaseDate = $item->getReleaseDate();
                            $release_date = '';
                            if (! is_null($releaseDate)) {
                                $release_date = $releaseDate->format('Y-m-d H:i:s');
                            }

                            $preDetailsList = $item->getPrepDetailsList();
                            $pre_details_list = [];
                            if (! is_null($preDetailsList)) {
                                foreach ($preDetailsList as $prepDetails) {
                                    $prep_instruction = $prepDetails->getPrepInstruction()->toString();
                                    $prep_owner = $prepDetails->getPrepOwner()->toString();
                                    $pre_details_list[] = [
                                        'prep_instruction' => $prep_instruction,
                                        'prep_owner' => $prep_owner,
                                    ];
                                }
                            }

                            //                        try {
                            //                            $detailCollection = AmazonShipmentItemsModel::query()
                            //                                ->where('merchant_id', $merchant_id)
                            //                                ->where('merchant_store_id', $merchant_store_id)
                            //                                ->where('shipment_id', $shipment_id)
                            //                                ->where('seller_sku', $seller_sku)
                            //                                ->firstOrFail();
                            //                        } catch (ModelNotFoundException) {
                            //                            $collections->push([
                            //                                'merchant_id' => $merchant_id,
                            //                                'merchant_store_id' => $merchant_store_id,
                            //                                'shipment_id' => $shipment_id,
                            //                                'seller_sku' => $seller_sku,
                            //                                'fulfillment_network_sku' => $fulfillment_network_sku,
                            //                                'quantity_shipped' => $quantity_shipped,
                            //                                'quantity_received' => $quantity_received,
                            //                                'quantity_in_case' => $quantity_in_case,
                            //                                'release_date' => $release_date,
                            //                                'prep_details_list' => json_encode($pre_details_list, JSON_THROW_ON_ERROR),
                            //                                'created_at' => $now
                            //                            ]);
                            //                            continue;
                            //                        }
                            //
                            // //                        $detailCollection->merchant_id = $merchant_id;
                            // //                        $detailCollection->merchant_store_id = $merchant_store_id;
                            // //                        $detailCollection->shipment_id = $shipment_id;
                            // //                        $detailCollection->seller_sku = $seller_sku;
                            //                        $detailCollection->fulfillment_network_sku = $fulfillment_network_sku;
                            //                        $detailCollection->quantity_shipped = $quantity_shipped;
                            //                        $detailCollection->quantity_received = $quantity_received;
                            //                        $detailCollection->quantity_in_case = $quantity_in_case;
                            //                        $detailCollection->release_date = $release_date;
                            //                        $detailCollection->prep_details_list = json_encode($pre_details_list, JSON_THROW_ON_ERROR);
                            //
                            //                        $detailCollection->save();

                            $collections->push([
                                'merchant_id' => $merchant_id,
                                'merchant_store_id' => $merchant_store_id,
                                'shipment_id' => $shipment_id,
                                'seller_sku' => $seller_sku,
                                'fulfillment_network_sku' => $fulfillment_network_sku,
                                'quantity_shipped' => $quantity_shipped,
                                'quantity_received' => $quantity_received,
                                'quantity_in_case' => $quantity_in_case,
                                'release_date' => $release_date,
                                'prep_details_list' => json_encode($pre_details_list, JSON_THROW_ON_ERROR),
                                'created_at' => $now,
                            ]);

                            $seller_skus[] = $seller_sku;
                        }

                        $next_token = $payload->getNextToken();

                        if (is_null($next_token)) {
                            break;
                        }
                    } catch (ApiException $e) {
                        if (! is_null($e->getResponseBody())) {
                            $body = json_decode($e->getResponseBody(), true, 512, JSON_THROW_ON_ERROR);
                            if (isset($body['errors'])) {
                                $errors = $body['errors'];
                                foreach ($errors as $error) {
                                    if ($error['code'] !== 'QuotaExceeded') {
                                        $console->warning(sprintf('merchant_id:%s merchant_store_id:%s Page:%s code:%s message:%s', $merchant_id, $merchant_store_id, $page, $error['code'], $error['message']));
                                        break 2;
                                    }
                                }
                            }
                        }

                        --$retry;
                        if ($retry > 0) {
                            $console->warning(sprintf('merchant_id:%s merchant_store_id:%s Page:%s 第 %s 次重试', $merchant_id, $merchant_store_id, $page, $retry));
                            sleep(3);
                            continue;
                        }

                        $console->error(sprintf('merchant_id:%s merchant_store_id:%s Page:%s 重试次数已用完', $merchant_id, $merchant_store_id, $page));
                        break;
                    } catch (InvalidArgumentException $e) {
                        break;
                    }
                }

                if ($collections->isEmpty()) {
                    continue;
                }

                AmazonShipmentItemsModel::insert($collections->all());

                $console->notice(sprintf('FulfillmentInbound getShipmentItemsByShipmentId shipment_id:%s merchant_id:%s merchant_store_id:%s 完成处理. 更新:%s 新增:%s', $shipment_id, $merchant_id, $merchant_store_id, $collections->count(), $collections->count()));
            }

            return true;
        });
    }
}
