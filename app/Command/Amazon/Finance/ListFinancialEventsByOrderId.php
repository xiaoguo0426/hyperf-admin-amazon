<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Command\Amazon\Finance;

use AmazonPHP\SellingPartner\AccessToken;
use AmazonPHP\SellingPartner\Exception\ApiException;
use AmazonPHP\SellingPartner\Exception\InvalidArgumentException;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use App\Model\AmazonOrderModel;
use App\Util\Amazon\Finance\AdhocDisbursementEventList;
use App\Util\Amazon\Finance\AdjustmentEventList;
use App\Util\Amazon\Finance\AffordabilityExpenseEventList;
use App\Util\Amazon\Finance\AffordabilityExpenseReversalEventList;
use App\Util\Amazon\Finance\CapacityReservationBillingEventList;
use App\Util\Amazon\Finance\ChargebackEventList;
use App\Util\Amazon\Finance\ChargeRefundEventList;
use App\Util\Amazon\Finance\CouponPaymentEventList;
use App\Util\Amazon\Finance\DebtRecoveryEventList;
use App\Util\Amazon\Finance\FailedAdhocDisbursementEventList;
use App\Util\Amazon\Finance\FbaLiquidationEventList;
use App\Util\Amazon\Finance\FinanceFactory;
use App\Util\Amazon\Finance\GuaranteeClaimEventList;
use App\Util\Amazon\Finance\ImagingServicesFeeEventList;
use App\Util\Amazon\Finance\LoanServicingEventList;
use App\Util\Amazon\Finance\NetworkComminglingTransactionEventList;
use App\Util\Amazon\Finance\PayWithAmazonEventList;
use App\Util\Amazon\Finance\ProductAdsPaymentEventList;
use App\Util\Amazon\Finance\RefundEventList;
use App\Util\Amazon\Finance\RemovalShipmentAdjustmentEventList;
use App\Util\Amazon\Finance\RemovalShipmentEventList;
use App\Util\Amazon\Finance\RentalTransactionEventList;
use App\Util\Amazon\Finance\RetroChargeEventList;
use App\Util\Amazon\Finance\SAFETReimbursementEventList;
use App\Util\Amazon\Finance\SellerDealPaymentEventList;
use App\Util\Amazon\Finance\SellerReviewEnrollmentPaymentEventList;
use App\Util\Amazon\Finance\ServiceFeeEventList;
use App\Util\Amazon\Finance\ServiceProviderCreditEventList;
use App\Util\Amazon\Finance\ShipmentEventList;
use App\Util\Amazon\Finance\ShipmentSettleEventList;
use App\Util\Amazon\Finance\TaxWithholdingEventList;
use App\Util\Amazon\Finance\TdsReimbursementEventList;
use App\Util\Amazon\Finance\TrialShipmentEventList;
use App\Util\Amazon\Finance\ValueAddedServiceChargeEventList;
use App\Util\AmazonApp;
use App\Util\AmazonSDK;
use App\Util\Log\AmazonFinanceLog;
use App\Util\RuntimeCalculator;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Dag\Dag;
use Hyperf\Dag\Vertex;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[Command]
class ListFinancialEventsByOrderId extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('amazon:finance:list-financial-events-by-order-id');
    }

    public function configure(): void
    {
        parent::configure();
        $this->addArgument('merchant_id', InputArgument::REQUIRED, '商户id')
            ->addArgument('merchant_store_id', InputArgument::REQUIRED, '店铺id')
            ->addOption('order_ids', null, InputOption::VALUE_OPTIONAL, 'order_ids集合', null)
            ->setDescription('Amazon Finance List Financial Events By Order Id Command');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function handle(): void
    {
        $merchant_id = (int) $this->input->getArgument('merchant_id');
        $merchant_store_id = (int) $this->input->getArgument('merchant_store_id');
        $amazon_order_ids = $this->input->getOption('order_ids');

        AmazonApp::tok($merchant_id, $merchant_store_id, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) use ($amazon_order_ids) {
            $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
            $logger = ApplicationContext::getContainer()->get(AmazonFinanceLog::class);

            if (! is_null($amazon_order_ids)) {
                $amazon_order_ids = explode(',', $amazon_order_ids);
            }

            $amazonOrderCollections = AmazonOrderModel::query()
                ->where('merchant_id', $merchant_id)
                ->where('merchant_store_id', $merchant_store_id)
                ->when($amazon_order_ids, function ($query, $value) {
                    return $query->whereIn('amazon_order_id', $value);
                })->get();
            if ($amazonOrderCollections->isEmpty()) {
                var_dump(111);
                return true;
            }

            /**
             * @var AmazonOrderModel $amazonOrderCollection
             */
            foreach ($amazonOrderCollections as $amazonOrderCollection) {
                $amazon_order_id = $amazonOrderCollection->amazon_order_id;

                $nextToken = null;
                $retry = 30;
                $page = 1; // 分页数

                while (true) {
                    try {
                        $response = $sdk->finances()->listFinancialEventsByOrderId($accessToken, $region, $amazon_order_id);
                        $payload = $response->getPayload();
                        if ($payload === null) {
                            $console->warning(sprintf('merchant_id:%s merchant_store_id:%s payload为null', $merchant_id, $merchant_store_id));
                            break;
                        }
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
                            $console->error(sprintf('merchant_id:%s merchant_store_id:%s 发生错误 %s', $merchant_id, $merchant_store_id, json_encode($errors, JSON_THROW_ON_ERROR)));
                            break;
                        }

                        $financialEvents = $payload->getFinancialEvents();
                        if (is_null($financialEvents)) {
                            break;
                        }

                        $dag = new Dag();

                        $eventList = [
                            ShipmentEventList::class => $financialEvents->getShipmentEventList(),
                            ShipmentSettleEventList::class => $financialEvents->getShipmentSettleEventList(),
                            RefundEventList::class => $financialEvents->getRefundEventList(),
                            GuaranteeClaimEventList::class => $financialEvents->getGuaranteeClaimEventList(),
                            ChargebackEventList::class => $financialEvents->getChargebackEventList(),
                            PayWithAmazonEventList::class => $financialEvents->getPayWithAmazonEventList(),
                            ServiceProviderCreditEventList::class => $financialEvents->getServiceProviderCreditEventList(),
                            RetroChargeEventList::class => $financialEvents->getRetrochargeEventList(),
                            RentalTransactionEventList::class => $financialEvents->getRentalTransactionEventList(),
                            ProductAdsPaymentEventList::class => $financialEvents->getProductAdsPaymentEventList(),
                            ServiceFeeEventList::class => $financialEvents->getServiceFeeEventList(),
                            SellerDealPaymentEventList::class => $financialEvents->getSellerDealPaymentEventList(),
                            DebtRecoveryEventList::class => $financialEvents->getDebtRecoveryEventList(),
                            LoanServicingEventList::class => $financialEvents->getLoanServicingEventList(),
                            AdjustmentEventList::class => $financialEvents->getAdjustmentEventList(),
                            SAFETReimbursementEventList::class => $financialEvents->getSafetReimbursementEventList(),
                            SellerReviewEnrollmentPaymentEventList::class => $financialEvents->getSellerReviewEnrollmentPaymentEventList(),
                            FbaLiquidationEventList::class => $financialEvents->getFbaLiquidationEventList(),
                            CouponPaymentEventList::class => $financialEvents->getCouponPaymentEventList(),
                            ImagingServicesFeeEventList::class => $financialEvents->getImagingServicesFeeEventList(),
                            NetworkComminglingTransactionEventList::class => $financialEvents->getNetworkComminglingTransactionEventList(),
                            AffordabilityExpenseEventList::class => $financialEvents->getAffordabilityExpenseEventList(),
                            AffordabilityExpenseReversalEventList::class => $financialEvents->getAffordabilityExpenseReversalEventList(),
                            RemovalShipmentEventList::class => $financialEvents->getRemovalShipmentEventList(),
                            RemovalShipmentAdjustmentEventList::class => $financialEvents->getRemovalShipmentAdjustmentEventList(),
                            TrialShipmentEventList::class => $financialEvents->getTrialShipmentEventList(),
                            TdsReimbursementEventList::class => $financialEvents->getTdsReimbursementEventList(),
                            AdhocDisbursementEventList::class => $financialEvents->getAdhocDisbursementEventList(),
                            TaxWithholdingEventList::class => $financialEvents->getTaxWithholdingEventList(),
                            ChargeRefundEventList::class => $financialEvents->getChargeRefundEventList(),
                            CapacityReservationBillingEventList::class => $financialEvents->getCapacityReservationBillingEventList(),
                        ];

                        foreach ($eventList as $eventName => $financialEventList) {
                            $dag->addVertex(Vertex::make(static function () use ($merchant_id, $merchant_store_id, $eventName, $financialEventList, $console) {
                                $finance = FinanceFactory::getInstance($merchant_id, $merchant_store_id, $eventName);
                                $event = $finance->getEventName();
                                if (! is_null($financialEventList) && count($financialEventList) > 0) {
                                    $runtimeCalculator = new RuntimeCalculator();
                                    $runtimeCalculator->start();

                                    $console->info(sprintf('正在处理财务事件[%s]', $event));
                                    $finance->run($financialEventList);
                                    $console->info(sprintf('财务事件[%s]处理完成,耗时:%s', $event, $runtimeCalculator->stop()));
                                } else {
                                    $console->warning(sprintf('[%s]没有该指标财务数据', $event));
                                }
                            }));
                        }

                        $eventObjectList = [
                            FailedAdhocDisbursementEventList::class => $financialEvents->getFailedAdhocDisbursementEventList(),
                            ValueAddedServiceChargeEventList::class => $financialEvents->getValueAddedServiceChargeEventList(),
                        ];
                        foreach ($eventObjectList as $eventName => $eventObject) {
                            $dag->addVertex(Vertex::make(static function () use ($merchant_id, $merchant_store_id, $eventName, $eventObject, $console) {
                                $finance = FinanceFactory::getInstance($merchant_id, $merchant_store_id, $eventName);
                                $event = $finance->getEventName();
                                if (! is_null($eventObject)) {
                                    $runtimeCalculator = new RuntimeCalculator();
                                    $runtimeCalculator->start();

                                    $console->info(sprintf('正在处理财务事件[%s]', $event));
                                    $finance->run($eventObject);
                                    $console->info(sprintf('财务事件[%s]处理完成,耗时:%s', $event, $runtimeCalculator->stop()));
                                } else {
                                    $console->warning(sprintf('[%s]没有该指标财务数据', $event));
                                }
                            }));
                        }

                        $dag->run();

                        $nextToken = $payload->getNextToken();
                        if (is_null($nextToken)) {
                            break;
                        }
                    } catch (ApiException $exception) {
                        if (! is_null($exception->getResponseBody())) {
                            $body = json_decode($exception->getResponseBody(), true, 512, JSON_THROW_ON_ERROR);
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

                        continue;
                    } catch (InvalidArgumentException $exception) {
                        $log = sprintf('merchant_id:%s merchant_store_id:%s InvalidArgumentException ', $merchant_id, $merchant_store_id);
                        $console->error($log);
                        $logger->error($log);
                        break;
                    }

                    $retry = 30; // 重置重试次数
                    ++$page;
                }
            }

            return true;
        });
    }
}
