<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Command\Amazon\Listings;

use AmazonPHP\SellingPartner\AccessToken;
use AmazonPHP\SellingPartner\Exception\ApiException;
use AmazonPHP\SellingPartner\Exception\InvalidArgumentException;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use App\Util\AmazonApp;
use App\Util\AmazonSDK;
use App\Util\Log\AmazonListingGetListingItemLog;
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
class GetListingsItem extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('amazon:listings:get-listings-item');
    }

    public function configure(): void
    {
        parent::configure();

        $this->addArgument('merchant_id', InputArgument::REQUIRED, '商户id')
            ->addArgument('merchant_store_id', InputArgument::REQUIRED, '店铺id')
            ->addArgument('region', InputArgument::REQUIRED, '地区')
            ->addArgument('marketplace_ids', InputArgument::REQUIRED, '市场ID，多个以英文逗号分隔')
            ->addArgument('seller_sku', InputArgument::REQUIRED, 'Seller SKU')
            ->setDescription('Amazon Listings Get Listings Item Command');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws NotFoundException
     * @throws \RedisException
     */
    public function handle(): void
    {
        $merchant_id = (int) $this->input->getArgument('merchant_id');
        $merchant_store_id = (int) $this->input->getArgument('merchant_store_id');
        $region = $this->input->getArgument('region');
        $real_marketplace_ids = $this->input->getArgument('marketplace_ids');
        $seller_sku = $this->input->getArgument('seller_sku');

        AmazonApp::tok2($merchant_id, $merchant_store_id, $region, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) use ($real_marketplace_ids, $seller_sku) {
            $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
            $logger = ApplicationContext::getContainer()->get(AmazonListingGetListingItemLog::class);

            $seller_id = $amazonSDK->getSellerId();

            $retry = 10;

            $marketplace_id_list = explode(',', $real_marketplace_ids);
            $included_data = [
                'summaries',
                'attributes',
                'issues',
                'offers',
                'fulfillmentAvailability',
                'procurement',
            ];

            foreach ($marketplace_id_list as $marketplace_id) {
                while (true) {
                    $console->info(sprintf('GetListingsItem merchant_id:%s merchant_store_id:%s marketplace_id:%s seller_sku:%s', $merchant_id, $merchant_store_id, $real_marketplace_ids, $seller_sku));

                    try {
                        $response = $sdk->listingsItems()->getListingsItem($accessToken, $region, $seller_id, $seller_sku, [$marketplace_id], null, $included_data);
                        $seller_sku = $response->getSku();
                        $itemSummaryByMarketplace = $response->getSummaries();
                        $summaries = [];
                        if (! is_null($itemSummaryByMarketplace)) {
                            foreach ($itemSummaryByMarketplace as $summary) {
                                $marketplace_id = $summary->getMarketplaceId();
                                $asin = $summary->getAsin();
                                $product_type = $summary->getProductType();
                                $condition_type = $summary->getConditionType() ?? '';
                                $status = $summary->getStatus();
                                $fn_sku = $summary->getFnSku() ?? '';
                                $item_name = $summary->getItemName();
                                $created_date = $summary->getCreatedDate()->format('Y-m-d H:i:s');
                                $last_updated_date = $summary->getLastUpdatedDate()->format('Y-m-d H:i:s');
                                $itemImage = $summary->getMainImage();
                                $link = '';
                                $height = '';
                                $width = '';
                                if (! is_null($itemImage)) {
                                    $link = $itemImage->getLink();
                                    $height = $itemImage->getHeight();
                                    $width = $itemImage->getWidth();
                                }
////                                var_dump($marketplace_id);
////                                var_dump($asin);
//                                var_dump($product_type);
////                                var_dump($condition_type);
//                                var_dump($status);
////                                var_dump($fn_sku);
////                                var_dump($item_name);
//                                var_dump($created_date);
//                                var_dump($last_updated_date);
                                $summaries[] = [
                                    'marketplace_id' => $marketplace_id,
                                    'asin' => $asin,
                                    'product_type' => $product_type,
                                    'condition_type' => $condition_type,
                                    'status' => $status,
                                    'fn_sku' => $fn_sku,
                                    'item_name' => $item_name,
                                    'created_date' => $created_date,
                                    'last_updated_date' => $last_updated_date,
                                    'image' => [
                                        'link' => $link,
                                        'height' => $height,
                                        'width' => $width,
                                    ],
                                ];
                            }
                        }
//                        var_dump($response->getAttributes());
//                        var_dump($response->getIssues());
//                        var_dump($response->getOffers());
//                        var_dump($response->getFulfillmentAvailability());
//                        var_dump($response->getProcurement());

                        $issues = $response->getIssues();
                        $issues_data = [];
                        if (! is_null($issues)) {
                            foreach ($issues as $issue) {
                                $issue_code = $issue->getCode();
                                $issue_message = $issue->getMessage();
                                $severity = $issue->getSeverity();
                                $categories = $issue->getCategories();
                                $attribute_names = $issue->getAttributeNames();

                                $issues_data[] = [
                                    'code' => $issue_code,
                                    'message' => $issue_message,
                                    'severity' => $severity,
                                    'categories' => $categories,
                                    'attribute_names' => $attribute_names,
                                ];
                            }
                        }

                        $itemOfferByMarketplace = $response->getOffers();
                        $offers = [];
                        if (! is_null($itemOfferByMarketplace)) {
                            foreach ($itemOfferByMarketplace as $offer) {

                                $marketplace_id = $offer->getMarketplaceId();
                                $offer_type = $offer->getOfferType();
                                $price = $offer->getPrice();
                                $points = $offer->getPoints();

                                $offers[] = [
                                    'marketplace_id' => $marketplace_id,
                                    'offer_type' => $offer_type,
                                    'price' => $price,
                                    'points' => $points,
                                ];
                            }
                        }

                        $fulfillmentAvailability = $response->getFulfillmentAvailability();
                        $fulfillment_availability = [];
                        if (! is_null($fulfillmentAvailability)) {
                            foreach ($fulfillmentAvailability as $fulfillmentAvailabilityItem) {
                                $fulfillment_channel_code = $fulfillmentAvailabilityItem->getFulfillmentChannelCode();
                                $quantity = $fulfillmentAvailabilityItem->getQuantity() ?? 0;

                                $fulfillment_availability[] = [
                                    'fulfillment_channel_code' => $fulfillment_channel_code,
                                    'quantity' => $quantity,
                                ];
                            }

                        }

                        $procurements = $response->getProcurement();
                        $procurements_data = [];
                        if (! is_null($procurements)) {
                            foreach ($procurements as $procurement) {
                                $costPrice = $procurement->getCostPrice();
                                $procurements_data[] = [
                                    'currency_code' => $costPrice->getCurrencyCode(),
                                    'amount' => $costPrice->getAmount()
                                ];
                            }
                        }

                        echo json_encode([
                            'summaries' => $summaries,
                            'attributes' => $response->getAttributes(),
                            'issues' => $issues_data,
                            'offers' => $offers,
                            'fulfillment_availability' => $fulfillment_availability,
                            'procurement' => $procurements_data,
                        ]);
                        break;
                    } catch (ApiException $e) {
                        if (! is_null($e->getResponseBody())) {
                            $body = json_decode($e->getResponseBody(), true, 512, JSON_THROW_ON_ERROR);
                            if (isset($body['errors'])) {
                                $errors = $body['errors'];
                                foreach ($errors as $error) {
                                    $console->warning(sprintf('GetListingsItem merchant_id:%s merchant_store_id:%s seller_sku:%s error:%s', $merchant_id, $merchant_store_id, $seller_sku, $error['message']));

                                    if ($error['code'] === 'NOT_FOUND') {
                                        break 2;
                                    }
                                }
                            }
                        }

                        --$retry;
                        if ($retry > 0) {
                            $console->warning(sprintf('GetListingsItem merchant_id:%s merchant_store_id:%s seller_sku:%s retry:%s', $merchant_id, $merchant_store_id, $seller_sku, $retry));
                            continue;
                        }

                        $log = sprintf('GetListingsItem 重试机会已耗尽. merchant_id:%s merchant_store_id:%s seller_sku:%s', $merchant_id, $merchant_store_id, $seller_sku);
                        $console->error($log, [
                            'message' => $e->getMessage(),
                            'response body' => $e->getResponseBody(),
                        ]);
                        $logger->error($log, [
                            'message' => $e->getMessage(),
                            'response body' => $e->getResponseBody(),
                        ]);
                        $retry = 10;
                        break;
                    } catch (InvalidArgumentException $e) {
                        $log = 'GetOrderMetrics 请求出错 InvalidArgumentException %s merchant_id:% merchant_store_id:%s ' . $e->getMessage();
                        $console->error($log);
                        break;
                    }
                }
            }

            return true;
        });
    }
}
