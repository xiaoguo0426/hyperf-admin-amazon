<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Command\Crontab\Amazon;

use AmazonPHP\SellingPartner\AccessToken;
use AmazonPHP\SellingPartner\Exception\ApiException;
use AmazonPHP\SellingPartner\Exception\InvalidArgumentException;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use App\Model\AmazonInventoryModel;
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

#[Command]
class GetListingsItem extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('crontab:amazon:get-listings-item');
        // 指令配置
        $this->setDescription('Crontab Amazon Listing API Get Listings Item Command');
    }

    /**
     * @throws NotFoundException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \RedisException
     */
    public function handle(): void
    {
        AmazonApp::each(static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) {
            $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
            $logger = ApplicationContext::getContainer()->get(AmazonListingGetListingItemLog::class);

            $seller_id = $amazonSDK->getSellerId();

            $amazonInventoryCollections = AmazonInventoryModel::query()
                ->where('merchant_id', $merchant_id)
                ->where('merchant_store_id', $merchant_store_id)
                ->where('region', $region)
                ->whereIn('marketplace_id', $marketplace_ids)
                ->get();

            if ($amazonInventoryCollections->isEmpty()) {
                return true;
            }

            foreach ($amazonInventoryCollections as $amazonInventoryCollection) {
                $marketplace_id = $amazonInventoryCollection->marketplace_id;
                $seller_sku = $amazonInventoryCollection->seller_sku;

                $retry = 10;
                while (true) {
                    $console->info(sprintf('GetListingsItem merchant_id:%s merchant_store_id:%s region:%s marketplace_id:%s seller_sku:%s', $merchant_id, $merchant_store_id, $region, $marketplace_id, $seller_sku));

                    try {
                        $response = $sdk->listingsItems()->getListingsItem($accessToken, $region, $seller_id, $seller_sku, [$marketplace_id], null, null);
                        $seller_sku = $response->getSku();
                        $summaries = $response->getSummaries();
                        if (! is_null($summaries)) {
                            foreach ($summaries as $summary) {
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
                                if (! is_null($itemImage)) {
                                    $link = $itemImage->getLink();
                                    $height = $itemImage->getHeight();
                                    $width = $itemImage->getWidth();
                                }
                                var_dump($marketplace_id);
                                var_dump($asin);
                                var_dump($product_type);
                                var_dump($condition_type);
                                var_dump($status);
                                var_dump($fn_sku);
                                var_dump($item_name);
                                var_dump($created_date);
                                var_dump($last_updated_date);
                            }
                        }
                        var_dump($response->getAttributes());
                        var_dump($response->getIssues());
                        var_dump($response->getOffers());
                        var_dump($response->getFulfillmentAvailability());
                        var_dump($response->getProcurement());

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
