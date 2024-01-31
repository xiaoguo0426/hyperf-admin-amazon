<?php

namespace App\Util\Amazon\Engine;

use AmazonPHP\SellingPartner\AccessToken;
use AmazonPHP\SellingPartner\Exception\ApiException;
use AmazonPHP\SellingPartner\Exception\InvalidArgumentException;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use App\Model\AmazonShipmentItemsModel;
use App\Util\Amazon\Creator\CreatorInterface;
use App\Util\Amazon\Creator\GetShipmentItemsCreator;
use App\Util\AmazonSDK;
use App\Util\Log\AmazonFulfillmentInboundGetShipmentItemsLog;
use DateTime;
use DateTimeZone;
use Exception;
use Hyperf\Collection\Collection;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Database\Model\ModelNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class GetShipmentItemsEngine implements EngineInterface
{
    /**
     * @param AmazonSDK $amazonSDK
     * @param SellingPartnerSDK $sdk
     * @param AccessToken $accessToken
     * @param CreatorInterface $creator
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     * @return bool
     */
    public function launch(AmazonSDK $amazonSDK, SellingPartnerSDK $sdk, AccessToken $accessToken, CreatorInterface $creator): bool
    {
        $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
        $logger = ApplicationContext::getContainer()->get(AmazonFulfillmentInboundGetShipmentItemsLog::class);

        /**
         * @var GetShipmentItemsCreator $creator
         */
        $query_type = $creator->getQueryType();
        $marketplace_id = $creator->getMarketplaceId();
        $last_updated_after = $creator->getLastUpdatedAfter();
        $last_updated_before = $creator->getLastUpdatedBefore();

        if (! is_null($last_updated_after)) {
            $last_updated_after = (new DateTime($last_updated_after, new DateTimeZone('UTC')));
        }
        if (! is_null($last_updated_before)) {
            $last_updated_before = (new DateTime($last_updated_before, new DateTimeZone('UTC')));
        }

        $region = $amazonSDK->getRegion();

        $merchant_id = $amazonSDK->getMerchantId();
        $merchant_store_id = $amazonSDK->getMerchantStoreId();

        $next_token = null;

        $console->info(sprintf('FulfillmentInbound merchant_id:%s merchant_store_id:%s region:%s 开始处理.', $merchant_id, $merchant_store_id, $region));

        $retry = 10;

        $collections = new Collection();

        while (true) {
            try {
                $response = $sdk->fulfillmentInbound()->getShipmentItems($accessToken, $region, $query_type, $marketplace_id, $last_updated_after, $last_updated_before, $next_token);

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

                $payload = $response->getPayload();
                if ($payload === null) {
                    break;
                }

                $inboundShipmentItems = $payload->getItemData();
                foreach ($inboundShipmentItems as $inboundShipmentItem) {
                    $shipment_id = $inboundShipmentItem->getShipmentId() ?? '';
                    $seller_sku = $inboundShipmentItem->getSellerSku();
                    $fulfillment_network_sku = $inboundShipmentItem->getFulfillmentNetworkSku();
                    $quantity_shipped = $inboundShipmentItem->getQuantityShipped();
                    $quantity_received = $inboundShipmentItem->getQuantityReceived() ?? 0;
                    $quantity_in_case = $inboundShipmentItem->getQuantityInCase() ?? 0;
                    $release_date = $inboundShipmentItem->getReleaseDate()?->format('Y-m-d H:i:s');
                    $prepDetailsList = $inboundShipmentItem->getPrepDetailsList();
                    $prep_details_list = [];
                    if (! is_null($prepDetailsList)) {
                        foreach ($prepDetailsList as $prepDetailsItem) {
                            $prep_instruction = $prepDetailsItem->getPrepInstruction()->toString();
                            $prep_owner = $prepDetailsItem->getPrepOwner()->toString();
                            $prep_details_list[] = [
                                'prep_instruction' => $prep_instruction,
                                'prep_owner' => $prep_owner,
                            ];
                        }
                    }

                    $collections->push([
                        'shipment_id' => $shipment_id,
                        'seller_sku' => $seller_sku,
                        'fulfillment_network_sku' => $fulfillment_network_sku,
                        'quantity_shipped' => $quantity_shipped,
                        'quantity_received' => $quantity_received,
                        'quantity_in_case' => $quantity_in_case,
                        'release_date' => $release_date,
                        'prep_details_list' => $prep_details_list,
                    ]);
                }

                $next_token = $payload->getNextToken();
                if (is_null($next_token)) {
                    break;
                }

                $retry = 10;
                $query_type = 'NEXT_TOKEN';
            } catch (ApiException $e) {
                --$retry;
                if ($retry > 0) {
                    $console->warning(sprintf('FulfillmentInbound ApiException GetShipments Failed. retry:%s merchant_id: %s merchant_store_id: %s region:%s ', $retry, $merchant_id, $merchant_store_id, $region));
                    sleep(10);
                    continue;
                }

                $log = sprintf('FulfillmentInbound ApiException GetShipments Failed. merchant_id: %s merchant_store_id: %s region:%s', $merchant_id, $merchant_store_id, $region);
                $console->error($log);
                $logger->error($log);
                break;
            } catch (InvalidArgumentException $e) {
                $log = sprintf('FulfillmentInbound InvalidArgumentException GetShipments Failed. merchant_id: %s merchant_store_id: %s region:%s', $merchant_id, $merchant_store_id, $region);
                $console->error($log);
                $logger->error($log);
                break;
            }
        }

        $collections->each(function ($collection) use ($merchant_id, $merchant_store_id) {

            $shipment_id = $collection['shipment_id'];
            $seller_sku = $collection['seller_sku'];
            $fulfillment_network_sku = $collection['fulfillment_network_sku'];
            $quantity_shipped = $collection['quantity_shipped'];
            $quantity_received = $collection['quantity_received'];
            $quantity_in_case = $collection['quantity_in_case'];
            $release_date = $collection['release_date'];
            $prep_details_list = $collection['prep_details_list'];

            try {
                $detailCollection = AmazonShipmentItemsModel::query()
                    ->where('merchant_id', $merchant_id)
                    ->where('merchant_store_id', $merchant_store_id)
                    ->where('shipment_id', $shipment_id)
                    ->where('seller_sku', $seller_sku)
                    ->firstOrFail();
            } catch (ModelNotFoundException) {
                $detailCollection = new AmazonShipmentItemsModel();
                $detailCollection->merchant_id = $merchant_id;
                $detailCollection->merchant_store_id = $merchant_store_id;
                $detailCollection->shipment_id = $shipment_id;
                $detailCollection->seller_sku = $seller_sku;
                $detailCollection->fulfillment_network_sku = $fulfillment_network_sku;
            }

            $detailCollection->quantity_shipped = $quantity_shipped;
            $detailCollection->quantity_received = $quantity_received;
            $detailCollection->quantity_in_case = $quantity_in_case;
            $detailCollection->release_date = $release_date;
            $detailCollection->prep_details_list = json_encode($prep_details_list, JSON_THROW_ON_ERROR);

            $detailCollection->save();

        });

        return true;
    }
}