<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Command\Amazon\FulfillmentOutbound;

use AmazonPHP\SellingPartner\AccessToken;
use AmazonPHP\SellingPartner\Exception\ApiException;
use AmazonPHP\SellingPartner\Exception\InvalidArgumentException;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use App\Util\AmazonApp;
use App\Util\AmazonSDK;
use App\Util\Log\AmazonFulfillmentOutboundListAllFulfillmentOrdersLog;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Di\Exception\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[Command]
class ListAllFulfillmentOrders extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('amazon:fulfillment-outbound:list-all-fulfillment-orders');
    }

    public function configure(): void
    {
        parent::configure();
        $this->addArgument('merchant_id', InputArgument::REQUIRED, '商户id')
            ->addArgument('merchant_store_id', InputArgument::REQUIRED, '店铺id')
            ->addOption('query_start_date', null, InputOption::VALUE_OPTIONAL, '用于选择在指定时间之后（或在指定时间）最后更新的履行订单的日期', null)
            ->setDescription('Amazon Fulfillment Outbound API List All Fulfillment Orders Command');
    }

    /**
     * @throws NotFoundException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \RedisException
     */
    public function handle(): void
    {
        $merchant_id = (int) $this->input->getArgument('merchant_id');
        $merchant_store_id = (int) $this->input->getArgument('merchant_store_id');
        $query_start_date = $this->input->getOption('query_start_date');

        AmazonApp::tok($merchant_id, $merchant_store_id, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) use ($query_start_date) {
            $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
            $logger = ApplicationContext::getContainer()->get(AmazonFulfillmentOutboundListAllFulfillmentOrdersLog::class);

            if (! is_null($query_start_date)) {
                $query_start_date = (new \DateTime($query_start_date, new \DateTimeZone('UTC')));
            }

            $retry = 10;
            $next_token = null;
            while (true) {
                try {
                    $response = $sdk->fulfillmentOutbound()->listAllFulfillmentOrders($accessToken, $region, $query_start_date, $next_token);

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

                    $listAllFulfillmentOrdersResult = $response->getPayload();
                    if (is_null($listAllFulfillmentOrdersResult)) {
                        break;
                    }

                    $fulfillmentOrders = $listAllFulfillmentOrdersResult->getFulfillmentOrders();
                    foreach ($fulfillmentOrders as $fulfillmentOrder) {
                        $seller_fulfillment_order_id = $fulfillmentOrder->getSellerFulfillmentOrderId(); // createFulfillmentOrder操作一起提交的履行订单标识符。
                        $marketplace_id = $fulfillmentOrder->getMarketplaceId() ?? ''; // 执行订单所针对的市场的标识符。
                        $displayable_order_id = $fulfillmentOrder->getDisplayableOrderId(); // 在面向客户的物料（如装箱单）中显示为订单标识符。
                        $displayable_order_date = $fulfillmentOrder->getDisplayableOrderDate()->format('Y-m-d H:i:s'); // 在面向客户的物料（如装箱单）中显示为订单日期。
                        $displayable_order_comment = $fulfillmentOrder->getDisplayableOrderComment(); // 使用提交子程序FulfillmentOrder操作提交的文本块。在包装单等面向客户的材料中显示。
                        $shipping_speed_category = $fulfillmentOrder->getShippingSpeedCategory()->toString(); // 用于完成订单的发运方法。当此值为ScheduledDelivery时，为fulfillmentAction选择Ship。当shippingSpeedCategory值为ScheduledDelivery时，Hold不是有效的fulfillmentAction值。

                        $deliveryWindow = $fulfillmentOrder->getDeliveryWindow(); // 计划交付履行订单应交付的时间范围。这只在JP市场上有售。
                        $delivery_window_start_date = '';
                        $delivery_window_end_date = '';
                        if (! is_null($deliveryWindow)) {
                            $delivery_window_start_date = $deliveryWindow->getStartDate()->format('Y-m-d H:i:s');
                            $delivery_window_end_date = $deliveryWindow->getEndDate()->format('Y-m-d H:i:s');
                        }

                        $destinationAddress = $fulfillmentOrder->getDestinationAddress(); // 通过createFulfillmentOrder操作提交的目标地址。
                        $destinationAddress->getName(); // 地址中的个人、企业或机构的名称。
                        $destinationAddress->getAddressLine1(); // 地址的第一行。
                        $destinationAddress->getAddressLine2(); // 地址的第二行。
                        $destinationAddress->getAddressLine3(); // 地址的第三行。
                        $destinationAddress->getCity(); // 城市
                        $destinationAddress->getDistrictOrCounty(); // 区或县
                        $destinationAddress->getStateOrRegion(); // 州或地区
                        $destinationAddress->getPostalCode(); // 地址的邮政编码
                        $destinationAddress->getCountryCode(); // 国家二字码
                        $destinationAddress->getPhone(); // 电话号码

                        $fulfillmentAction = $fulfillmentOrder->getFulfillmentAction(); // 履行行动
                        $fulfillment_action = '';
                        if (! is_null($fulfillmentAction)) {
                            $fulfillment_action = $fulfillmentAction->toString();
                        }

                        $fulfillmentPolicy = $fulfillmentOrder->getFulfillmentPolicy(); // 履行政策
                        $fulfillment_policy = '';
                        if (! is_null($fulfillmentPolicy)) {
                            $fulfillment_policy = $fulfillmentPolicy->toString();
                        }

                        $codSettings = $fulfillmentOrder->getCodSettings(); // 您与COD履行订单关联的COD（货到付款）费用。
                        $cod_settings = '';
                        if (! is_null($codSettings)) {
                            $is_cod_required = $codSettings->getIsCodRequired();
                            $cod_charge = $codSettings->getCodCharge();
                            $cod_charge_tax = $codSettings->getCodChargeTax();
                            $shipping_charge = $codSettings->getShippingCharge();
                            $shipping_charge_tax = $codSettings->getShippingChargeTax();

                            $cod_settings = json_encode([
                                'is_cod_required' => $is_cod_required,
                                'cod_charge' => $cod_charge,
                                'cod_charge_tax' => $cod_charge_tax,
                                'shipping_charge' => $shipping_charge,
                                'shipping_charge_tax' => $shipping_charge_tax,
                            ], JSON_THROW_ON_ERROR);
                        }

                        $fulfillmentOrder->getReceivedDate()->format('Y-m-d H:i:s'); // 接收日期
                        $fulfillment_order_status = $fulfillmentOrder->getFulfillmentOrderStatus()->toString(); // 完成订单的当前状态。
                        $fulfillmentOrder->getStatusUpdatedDate()->format('Y-m-d H:i:s'); // 状态更新日期
                        $notification_emails = $fulfillmentOrder->getNotificationEmails(); // 通知电子邮件.家提供的电子邮件地址列表，亚马逊使用这些地址代表卖家向收件人发送发货完成通知。
                        //                        $notificationEmails = $fulfillmentOrder->getNotificationEmails();
                        //                        foreach ($notificationEmails as $notificationEmail){
                        //                            $notificationEmail
                        //                        }
                        $featureConstraints = $fulfillmentOrder->getFeatureConstraints(); // 要应用于订单的功能及其履行策略的列表。
                        $feature_constraints = [];
                        if (! is_null($featureConstraints)) {
                            foreach ($featureConstraints as $featureConstraint) {
                                $feature_constraints[] = [
                                    'feature_name' => $featureConstraint->getFeatureName() ?? '',
                                    'feature_fulfillment_policy' => $featureConstraint->getFeatureFulfillmentPolicy() ?? '',
                                    'feature_fulfillment_policy_allowable_values' => $featureConstraint->getFeatureFulfillmentPolicyAllowableValues(),
                                ];
                            }
                        }

                        var_dump($seller_fulfillment_order_id);
                        var_dump($marketplace_id);
                        var_dump($displayable_order_id);
                        var_dump($displayable_order_date);
                        var_dump($displayable_order_comment);
                        var_dump($shipping_speed_category);
                        var_dump($delivery_window_start_date);
                        var_dump($delivery_window_end_date);
                        var_dump($fulfillment_action);
                        var_dump($fulfillment_policy);
                        var_dump($cod_settings);
                        var_dump($fulfillment_order_status);
                        var_dump($notification_emails);
                        var_dump($feature_constraints);
                    }

                    $next_token = $listAllFulfillmentOrdersResult->getNextToken();
                    if (is_null($next_token)) {
                        break;
                    }
                } catch (ApiException $e) {
                    --$retry;
                    if ($retry > 0) {
                        $console->warning(sprintf('FulfillmentOutbound ApiException ListAllFulfillmentOrders Failed. retry:%s merchant_id: %s merchant_store_id: %s region:%s ', $retry, $merchant_id, $merchant_store_id, $region));
                        sleep(10);
                        continue;
                    }

                    $log = sprintf('FulfillmentOutbound ApiException ListAllFulfillmentOrders Failed. merchant_id: %s merchant_store_id: %s region:%s', $merchant_id, $merchant_store_id, $region);
                    $console->error($log);
                    $logger->error($log);
                    break;
                } catch (InvalidArgumentException $e) {
                    $log = sprintf('FulfillmentOutbound InvalidArgumentException ListAllFulfillmentOrders Failed. merchant_id: %s merchant_store_id: %s region:%s', $merchant_id, $merchant_store_id, $region);
                    $console->error($log);
                    $logger->error($log);
                    break;
                }
            }
        });
    }
}
