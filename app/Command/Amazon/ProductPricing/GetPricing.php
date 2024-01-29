<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Command\Amazon\ProductPricing;

use AmazonPHP\SellingPartner\AccessToken;
use AmazonPHP\SellingPartner\Exception\ApiException;
use AmazonPHP\SellingPartner\Exception\InvalidArgumentException;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use App\Util\AmazonApp;
use App\Util\AmazonSDK;
use App\Util\Log\AmazonProductPricingGetFeaturedOfferExpectedPriceBatchLog;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\StdoutLoggerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[Command]
class GetPricing extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('amazon:product-pricing:get-pricing');
    }

    public function configure(): void
    {
        parent::configure();
        $this->addArgument('merchant_id', InputArgument::REQUIRED, '商户id')
            ->addArgument('merchant_store_id', InputArgument::REQUIRED, '店铺id')
            ->addArgument('region', InputArgument::REQUIRED, '地区')
            ->addArgument('item_type', InputArgument::REQUIRED, 'Item类型  Asin/Sku')
            ->addOption('marketplace_id', null, InputOption::VALUE_OPTIONAL, '市场id', null)
            ->addOption('asins', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'asin集合', null)
            ->addOption('skus', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'skus集合', null)
            ->addOption('item_conditions', null, InputOption::VALUE_OPTIONAL, '根据商品条件过滤报价列表 New/Used/Collectible/Refurbished/Club', null)
            ->addOption('offer_type', null, InputOption::VALUE_OPTIONAL, '指示是否为卖方的B2C或B2B报价请求定价信息.默认B2C', null)
            ->setDescription('Amazon ProductPricing API GetFeaturedOfferExpectedPriceBatch Command');
    }

    public function handle(): void
    {
        $merchant_id = (int) $this->input->getArgument('merchant_id');
        $merchant_store_id = (int) $this->input->getArgument('merchant_store_id');
        $region = $this->input->getArgument('region');
        $item_type = $this->input->getArgument('item_type');
        $marketplace_id = $this->input->getOption('marketplace_id');
        $asins = $this->input->getOption('asins');
        $skus = $this->input->getOption('skus');
        $item_conditions = $this->input->getOption('item_conditions');
        $offer_type = $this->input->getOption('offer_type');

        AmazonApp::tok2($merchant_id, $merchant_store_id, $region, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) use ($marketplace_id, $item_type, $asins, $skus, $item_conditions, $offer_type) {
            $logger = ApplicationContext::getContainer()->get(AmazonProductPricingGetFeaturedOfferExpectedPriceBatchLog::class);
            $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);

            $retry = 30;

            while (true) {
                try {
                    $response = $sdk->productPricing()->getPricing($accessToken, $region, $marketplace_id, $item_type, $asins, $skus, $item_conditions, $offer_type);

                    $errorsList = $response->getErrors();
                    if (! is_null($errorsList)) {
                        $errors = [];
                        foreach ($errorsList as $error) {
                            $errors[] = [
                                'code' => $error->getCode(),
                                'message' => $error->getMessage() ?? '',
                                'details' => $error->getDetails() ?? '',
                            ];
                        }
                        $console->error(sprintf('merchant_id:%s merchant_store_id:%s 处理 %s 市场数据发生错误 %s', $merchant_id, $merchant_store_id, $region, json_encode($errors, JSON_THROW_ON_ERROR)));
                        break;
                    }
                    $priceList = $response->getPayload();
                    if (is_null($priceList)) {
                        break;
                    }

                    $price_list = [];
                    foreach ($priceList as $priceItem) {
                        $status = $priceItem->getStatus();
                        $seller_sku = $priceItem->getSellerSku();
                        $asin = $priceItem->getAsin();
                        $product = $priceItem->getProduct();
                        if (is_null($product)) {
                            continue;
                        }

                        $identifierType = $product->getIdentifiers();
                        $asinIdentifier = $identifierType->getMarketplaceAsin();
                        $asin_identifier_asin = $asinIdentifier->getAsin();
                        $asin_identifier_marketplace_id = $asinIdentifier->getMarketplaceId();

                        $skuIdentifier = $identifierType->getSkuIdentifier();
                        $sku_identifier_marketplace_id = '';
                        $sku_identifier_seller_id = '';
                        $sku_identifier_seller_sku = '';
                        if (! is_null($skuIdentifier)) {
                            $sku_identifier_marketplace_id = $skuIdentifier->getMarketplaceId();
                            $sku_identifier_seller_id = $skuIdentifier->getSellerId();
                            $sku_identifier_seller_sku = $skuIdentifier->getSellerSku();
                        }

                        $attributeSetList = $product->getAttributeSets();
                        if (! is_null($attributeSetList)) {
                            foreach ($attributeSetList as $attributeSetItem) {
                                var_dump($attributeSetItem);
                            }
                        }

                        $relationshipList = $product->getRelationships();
                        if (! is_null($relationshipList)) {
                            foreach ($relationshipList as $relationshipItem) {
                                var_dump($relationshipItem);
                            }
                        }

                        $competitivePricingType = $product->getCompetitivePricing();
                        if (! is_null($competitivePricingType)) {
                            $competitivePriceList = $competitivePricingType->getCompetitivePrices();
                            foreach ($competitivePriceList as $competitivePriceItem) {
                                $competitivePriceItem->getCompetitivePriceId();//定价模型    1 - New Buy Box Price.  2 - Used Buy Box Price.
                                $priceType = $competitivePriceItem->getPrice();

                                $landedPrice = $priceType->getLandedPrice();
                                $landed_price = [];
                                if (! is_null($landedPrice)) {
                                    $landed_price = [
                                        'currency_code' => $landedPrice->getCurrencyCode() ?? '',
                                        'amount' => $landedPrice->getAmount() ?? 0.00
                                    ];
                                }
                                $listingPrice = $priceType->getListingPrice();
//                                $listing_price = [];
//                                if (! is_null($listingPrice)) {
                                $listing_price = [
                                    'currency_code' => $listingPrice->getCurrencyCode() ?? '',
                                    'amount' => $listingPrice->getAmount() ?? 0.00
                                ];
//                                }
                                $shippingPrice = $priceType->getShipping();
                                $shipping_price = [];
                                if (! is_null($shippingPrice)) {
                                    $shipping_price = [
                                        'currency_code' => $shippingPrice->getCurrencyCode() ?? '',
                                        'amount' => $shippingPrice->getAmount() ?? 0.00
                                    ];
                                }
                                $points = $priceType->getPoints();
                                if (! is_null($points)) {
                                    $points->getPointsNumber();
                                    $pointsMonetaryValue = $points->getPointsMonetaryValue();
                                    $points_monetary_value = [];
                                    if (! is_null($pointsMonetaryValue)) {
                                        $points_monetary_value = [
                                            'currency_code' => $pointsMonetaryValue->getCurrencyCode() ?? '',
                                            'amount' => $pointsMonetaryValue->getAmount() ?? 0.00
                                        ];
                                    }
                                }

                                $condition = $competitivePriceItem->getCondition();
                                $sub_condition = $competitivePriceItem->getSubcondition();
                                $offerCustomerType = $competitivePriceItem->getOfferType();
                                $offer_customer_type = '';
                                if (! is_null($offerCustomerType)) {
                                    $offer_customer_type = $offerCustomerType->toString();
                                }
                                $quantity_tier = $competitivePriceItem->getQuantityTier() ?? 0;
                                $quantityDiscountType = $competitivePriceItem->getQuantityDiscountType();
                                $quantity_discount_type = '';
                                if (! is_null($quantityDiscountType)) {
                                    $quantity_discount_type = $quantityDiscountType->toString();
                                }
                                $seller_id = $competitivePriceItem->getSellerId();
                                $belongs_to_requester = $competitivePriceItem->getBelongsToRequester() ?? false;

                            }
                            $competitivePricingType->getNumberOfOfferListings();
                            $competitivePricingType->getTradeInValue();
                        }

                        $salesRankList = $product->getSalesRankings();
                        $sales_ranking_list = [];
                        if (! is_null($salesRankList)) {
                            foreach ($salesRankList as $salesRankItem) {
                                $sales_ranking_list[] = [
                                    'product_category_id' => $salesRankItem->getProductCategoryId(),
                                    'rank' => $salesRankItem->getRank()
                                ];
                            }
                        }

                        $offers = $product->getOffers();

                        if (! is_null($offers)) {
                            foreach ($offers as $offer) {
                                $offer->getOfferType();
                                $offer->getBuyingPrice();
                                $offer->getRegularPrice();
                                $offer->getBusinessPrice();
                                $offer->getQuantityDiscountPrices();
                                $offer->getFulfillmentChannel();
                                $offer->getItemCondition();
                                $offer->getItemSubCondition();
                                $offer->getSellerSku();
                            }
                        }

                    }

                    break;
                } catch (ApiException $exception) {
                    --$retry;
                    if ($retry > 0) {
                        $console->warning(sprintf('ApiException Inventory API retry:%s Exception:%s', $retry, $exception->getMessage()));
                        sleep(10);
                        continue;
                    }
                    $console->error('ApiException Inventory API 重试次数耗尽', [
                        'message' => $exception->getMessage(),
                        'trace' => $exception->getTraceAsString(),
                    ]);

                    $logger->error('ApiException Inventory API 重试次数耗尽', [
                        'message' => $exception->getMessage(),
                        'trace' => $exception->getTraceAsString(),
                    ]);

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

        });

    }
}
