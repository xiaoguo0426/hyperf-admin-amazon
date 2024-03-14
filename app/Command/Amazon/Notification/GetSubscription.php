<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Command\Amazon\Notification;

use AmazonPHP\SellingPartner\AccessToken;
use AmazonPHP\SellingPartner\Exception\ApiException;
use AmazonPHP\SellingPartner\Exception\InvalidArgumentException;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use App\Util\AmazonApp;
use App\Util\AmazonSDK;
use App\Util\Log\AmazonSellerGetMarketplaceParticipationLog;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\StdoutLoggerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Input\InputArgument;

#[Command]
class GetSubscription extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('amazon:notification:get-subscription');
    }

    public function configure(): void
    {
        parent::configure();
        // 指令配置
        $this->addArgument('merchant_id', InputArgument::REQUIRED, '商户id')
            ->addArgument('merchant_store_id', InputArgument::REQUIRED, '店铺id')
            ->setDescription('Amazon Notification Get Subscription Command');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \RedisException
     */
    public function handle(): void
    {
        $merchant_id = (int) $this->input->getArgument('merchant_id');
        $merchant_store_id = (int) $this->input->getArgument('merchant_store_id');
        AmazonApp::tok($merchant_id, $merchant_store_id, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) {
            $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
            $logger = ApplicationContext::getContainer()->get(AmazonSellerGetMarketplaceParticipationLog::class);

            $retry = 10;

            //            $notification_type_list = [
            //                'ACCOUNT_STATUS_CHANGED',//每当开发者订阅的销售伙伴/市场对的账户状态发生变化时就会发送。每当销售伙伴的账户状态在 NORMAL，AT_RISK 以及 DEACTIVATED 之间发生变化时，就会发布通知。
            //                'ANY_OFFER_CHANGED',//每当按条件（全新或二手商品）排列的前 20 个报价中的任何一个发生变化，或卖家发布的商品的外部价格（来自其他零售商的价格）发生变化时就会发送。
            //                'B2B_ANY_OFFER_CHANGED',//每当前 20 个 B2B 报价中的任何一个发生变化，即卖家发布的商品的任何价格（单个商品或数量折扣分层定价）发生变化时，就会发送。
            //                'DETAIL_PAGE_TRAFFIC_EVENT',//每小时开始时发送。此通知共享 ASIN 级别的流量数据，包括前一小时的数据以及最多 24 小时前的任何延迟数据。每份通知可能包含多个 ASIN，预计销售伙伴每小时会收到多条通知。
            //                'FBA_INVENTORY_AVAILABILITY_CHANGES',//每当亚马逊物流 (FBA) 库存数量发生变化时就会发送。此通知包含特定地区所有符合条件的商城的亚马逊物流库存的定时快照。
            //                'FBA_OUTBOUND_SHIPMENT_STATUS',//每当我们为卖家创建或取消亚马逊物流货件时就会发送。
            //                'FEE_PROMOTION',//在促销活动生效时发送。
            //                'FEED_PROCESSING_FINISHED',//每当使用上传数据的销售伙伴 API 提交的任何上传数据的上传数据处理状态为 DONE、CANCELLED，或 FATAL 时就会发送。
            //                'FULFILLMENT_ORDER_STATUS',//每当多渠道配送订单的状态发生变化时就会发送。
            //                'ITEM_INVENTORY_EVENT_CHANGE',//每小时开始时发送。此通知共享 ASIN 级别的库存数据，并包括前一小时的数据以及最多 24 小时前的任何延迟数据。每份通知可能包含多个 ASIN，并且预计销售伙伴每小时会收到多条通知。
            //                'ITEM_SALES_EVENT_CHANGE',//每小时开始时发送。此通知共享 ASIN 级别的销售数据，并且包括前一小时的数据以及最多 24 小时前的任何延迟数据。每份通知可能包含多个 ASIN，预计销售伙伴每小时会收到多条通知
            //                'ORDER_CHANGE',//每当订单有重要变化时发送。重要变化包括订单状态更改和买家申请的取消订单。
            //                'ORDER_STATUS_CHANGE',//每当新订单或现有订单的可用性状态发生变化时就会发送。
            //                'PRICING_HEALTH',//每当卖家商品因价格不具竞争力而不符合精选商品的资格时就会发送。
            //                'REPORT_PROCESSING_FINISHED',//每当您使用报告的销售合作伙伴API请求的任何报告达到DONE、CANCELLED或FATAL的报告处理状态时发送。
            //            ];

            $notification_type_list = [
                'BRANDED_ITEM_CONTENT_CHANGE',
                //                'ITEM_PRODUCT_TYPE_CHANGE',
                //                'LISTINGS_ITEM_STATUS_CHANGE',
                //                'LISTINGS_ITEM_ISSUES_CHANGE',
                //                'LISTINGS_ITEM_MFN_QUANTITY_CHANGE',
                //                'PRODUCT_TYPE_DEFINITIONS_CHANGE',
            ];

            foreach ($notification_type_list as $notification_type) {
                while (true) {
                    try {
                        // https://developer-docs.amazon.com/sp-api/docs/notifications-api-v1-reference#getsubscription
                        $response = $sdk->notifications()->getSubscription($accessToken, $region, $notification_type);

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
                            $console->error(sprintf('merchant_id:%s merchant_store_id:%s  发生错误 %s', $merchant_id, $merchant_store_id, json_encode($errors, JSON_THROW_ON_ERROR)));
                            break;
                        }

                        $subscription = $response->getPayload();
                        if (is_null($subscription)) {
                            break;
                        }

                        $subscription_id = $subscription->getSubscriptionId();
                        $payload_version = $subscription->getPayloadVersion();
                        $destination_id = $subscription->getDestinationId();
                        var_dump($subscription_id);
                        var_dump($payload_version);
                        var_dump($destination_id);

                        $processingDirective = $subscription->getProcessingDirective();
                        if (! is_null($processingDirective)) {
                            $eventFilter = $processingDirective->getEventFilter();
                            if (! is_null($eventFilter)) {
                                $eventFilter->getAggregationSettings();
                                $eventFilter->getMarketplaceIds();
                                $eventFilter->getEventFilterType();
                            }
                        }

                        break;
                    } catch (ApiException $e) {
                        var_dump($e->getResponseBody());
                        //                        --$retry;
                        //                        if ($retry > 0) {
                        //                            continue;
                        //                        }
                        break;
                    } catch (InvalidArgumentException $e) {
                        break;
                    }
                }
            }
            return true;
        });
    }
}
