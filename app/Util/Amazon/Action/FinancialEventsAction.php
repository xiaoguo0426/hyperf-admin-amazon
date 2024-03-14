<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Util\Amazon\Action;

use AmazonPHP\SellingPartner\Model\Finances\FinancialEvents;
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
use App\Util\RuntimeCalculator;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Dag\Dag;
use Hyperf\Dag\Vertex;

class FinancialEventsAction implements ActionInterface
{
    private int $merchant_id;

    private int $merchant_store_id;

    private FinancialEvents $financialEvents;

    public function __construct(int $merchant_id, int $merchant_store_id, FinancialEvents $financialEvents)
    {
        $this->merchant_id = $merchant_id;
        $this->merchant_store_id = $merchant_store_id;
        $this->financialEvents = $financialEvents;
    }

    public function run(): bool
    {
        $dag = new Dag();

        $eventList = [
            // 配送
            ShipmentEventList::class => $this->financialEvents->getShipmentEventList(), // 亚马逊订单交易，包含亚马逊的订单收入及订单费用
            // 一个关于货物结算财务事件的信息列表
            ShipmentSettleEventList::class => $this->financialEvents->getShipmentSettleEventList(),
            // 退款
            RefundEventList::class => $this->financialEvents->getRefundEventList(), // 亚马逊订单退款，包含亚马逊的订单退款及订单退款
            // 一个网络混合交易事件
            GuaranteeClaimEventList::class => $this->financialEvents->getGuaranteeClaimEventList(),
            // 拒付
            ChargebackEventList::class => $this->financialEvents->getChargebackEventList(), // 买家信用卡拒付
            // 亚马逊支付
            PayWithAmazonEventList::class => $this->financialEvents->getPayWithAmazonEventList(), // 用户使用亚马逊账户绑定第三方平台进行收款，亚马逊会提供收款服务在此服务中扣除手续费
            ServiceProviderCreditEventList::class => $this->financialEvents->getServiceProviderCreditEventList(),
            // 赔偿撤销
            RetroChargeEventList::class => $this->financialEvents->getRetrochargeEventList(), // 分两种 order撤销和赔偿撤销。属于已经赔偿后重新撤销的金额，目前此事件含税费
            RentalTransactionEventList::class => $this->financialEvents->getRentalTransactionEventList(),
            // 广告
            ProductAdsPaymentEventList::class => $this->financialEvents->getProductAdsPaymentEventList(), // CPC广告服务，若卖家的广告服务使用卖家账户扣款则在此服务中结算，若选择信用卡支付则不在此服务中结算
            // 服务费
            ServiceFeeEventList::class => $this->financialEvents->getServiceFeeEventList(), // 亚马逊店铺或账号维度的服务费，主要包括订阅费、促销费等
            // 秒杀费用
            SellerDealPaymentEventList::class => $this->financialEvents->getSellerDealPaymentEventList(), // 亚马逊Lightning Deal Fee费用
            // 信用卡扣款
            DebtRecoveryEventList::class => $this->financialEvents->getDebtRecoveryEventList(), // 当用户应收金额不足以支付账单费用，在此类型中执行信用卡扣款业务，此类型不含广告费直接信用扣款
            // 贷款服务事件的列表
            LoanServicingEventList::class => $this->financialEvents->getLoanServicingEventList(),
            // 对卖方帐户的调整
            AdjustmentEventList::class => $this->financialEvents->getAdjustmentEventList(), // 亚马逊库存赔偿、亚马逊费用调整及预留金额
            // 一个SAFETReimbursementEvents.的列表
            SAFETReimbursementEventList::class => $this->financialEvents->getSafetReimbursementEventList(),
            // 早期评论人计划
            SellerReviewEnrollmentPaymentEventList::class => $this->financialEvents->getSellerReviewEnrollmentPaymentEventList(), // 亚马逊早期评论人计划扣款
            // 亚马逊库存清算服务
            FbaLiquidationEventList::class => $this->financialEvents->getFbaLiquidationEventList(), // 移除中类型为清算的订单，费用在此类型中结算
            // 优惠券手续费
            CouponPaymentEventList::class => $this->financialEvents->getCouponPaymentEventList(), // 产生促销订单后扣除优惠券的手续费用，0.06美元（美国）或60日元（日本）
            // 与亚马逊成像服务相关的收费事件列表
            ImagingServicesFeeEventList::class => $this->financialEvents->getImagingServicesFeeEventList(),
            // 一个网络混合交易事件的列表
            NetworkComminglingTransactionEventList::class => $this->financialEvents->getNetworkComminglingTransactionEventList(),
            // 与负担能力促销有关的支出信息列表
            AffordabilityExpenseEventList::class => $this->financialEvents->getAffordabilityExpenseEventList(),
            AffordabilityExpenseReversalEventList::class => $this->financialEvents->getAffordabilityExpenseReversalEventList(),
            RemovalShipmentEventList::class => $this->financialEvents->getRemovalShipmentEventList(),
            // 清算调整费用
            RemovalShipmentAdjustmentEventList::class => $this->financialEvents->getRemovalShipmentAdjustmentEventList(), // 对应summary中Liquidation Adjustment
            // 关于试运财务事件的信息列表
            TrialShipmentEventList::class => $this->financialEvents->getTrialShipmentEventList(),
            TdsReimbursementEventList::class => $this->financialEvents->getTdsReimbursementEventList(),
            AdhocDisbursementEventList::class => $this->financialEvents->getAdhocDisbursementEventList(),
            // 预扣税款事件列表
            TaxWithholdingEventList::class => $this->financialEvents->getTaxWithholdingEventList(),
            ChargeRefundEventList::class => $this->financialEvents->getChargeRefundEventList(),
            CapacityReservationBillingEventList::class => $this->financialEvents->getCapacityReservationBillingEventList(),
        ];

        $merchant_id = $this->merchant_id;
        $merchant_store_id = $this->merchant_store_id;
        $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);

        foreach ($eventList as $eventName => $financialEventList) {
            $dag->addVertex(Vertex::make(static function () use ($merchant_id, $merchant_store_id, $eventName, $financialEventList, $console): void {
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
        // 这两个事件返回的结果不是数组，所以单独处理
        $eventObjectList = [
            FailedAdhocDisbursementEventList::class => $this->financialEvents->getFailedAdhocDisbursementEventList(),
            ValueAddedServiceChargeEventList::class => $this->financialEvents->getValueAddedServiceChargeEventList(),
        ];
        foreach ($eventObjectList as $eventName => $eventObject) {
            $dag->addVertex(Vertex::make(static function () use ($merchant_id, $merchant_store_id, $eventName, $eventObject, $console): void {
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

        return true;
    }
}
