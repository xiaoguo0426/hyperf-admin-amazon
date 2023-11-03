<?php

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
            ShipmentEventList::class => $this->financialEvents->getShipmentEventList(),
            ShipmentSettleEventList::class => $this->financialEvents->getShipmentSettleEventList(),
            RefundEventList::class => $this->financialEvents->getRefundEventList(),
            GuaranteeClaimEventList::class => $this->financialEvents->getGuaranteeClaimEventList(),
            ChargebackEventList::class => $this->financialEvents->getChargebackEventList(),
            PayWithAmazonEventList::class => $this->financialEvents->getPayWithAmazonEventList(),
            ServiceProviderCreditEventList::class => $this->financialEvents->getServiceProviderCreditEventList(),
            RetroChargeEventList::class => $this->financialEvents->getRetrochargeEventList(),
            RentalTransactionEventList::class => $this->financialEvents->getRentalTransactionEventList(),
            ProductAdsPaymentEventList::class => $this->financialEvents->getProductAdsPaymentEventList(),
            ServiceFeeEventList::class => $this->financialEvents->getServiceFeeEventList(),
            SellerDealPaymentEventList::class => $this->financialEvents->getSellerDealPaymentEventList(),
            DebtRecoveryEventList::class => $this->financialEvents->getDebtRecoveryEventList(),
            LoanServicingEventList::class => $this->financialEvents->getLoanServicingEventList(),
            AdjustmentEventList::class => $this->financialEvents->getAdjustmentEventList(),
            SAFETReimbursementEventList::class => $this->financialEvents->getSafetReimbursementEventList(),
            SellerReviewEnrollmentPaymentEventList::class => $this->financialEvents->getSellerReviewEnrollmentPaymentEventList(),
            FbaLiquidationEventList::class => $this->financialEvents->getFbaLiquidationEventList(),
            CouponPaymentEventList::class => $this->financialEvents->getCouponPaymentEventList(),
            ImagingServicesFeeEventList::class => $this->financialEvents->getImagingServicesFeeEventList(),
            NetworkComminglingTransactionEventList::class => $this->financialEvents->getNetworkComminglingTransactionEventList(),
            AffordabilityExpenseEventList::class => $this->financialEvents->getAffordabilityExpenseEventList(),
            AffordabilityExpenseReversalEventList::class => $this->financialEvents->getAffordabilityExpenseReversalEventList(),
            RemovalShipmentEventList::class => $this->financialEvents->getRemovalShipmentEventList(),
            RemovalShipmentAdjustmentEventList::class => $this->financialEvents->getRemovalShipmentAdjustmentEventList(),
            TrialShipmentEventList::class => $this->financialEvents->getTrialShipmentEventList(),
            TdsReimbursementEventList::class => $this->financialEvents->getTdsReimbursementEventList(),
            AdhocDisbursementEventList::class => $this->financialEvents->getAdhocDisbursementEventList(),
            TaxWithholdingEventList::class => $this->financialEvents->getTaxWithholdingEventList(),
            ChargeRefundEventList::class => $this->financialEvents->getChargeRefundEventList(),
            CapacityReservationBillingEventList::class => $this->financialEvents->getCapacityReservationBillingEventList(),
        ];

        $merchant_id = $this->merchant_id;
        $merchant_store_id = $this->merchant_store_id;
        $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);

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
            FailedAdhocDisbursementEventList::class => $this->financialEvents->getFailedAdhocDisbursementEventList(),
            ValueAddedServiceChargeEventList::class => $this->financialEvents->getValueAddedServiceChargeEventList(),
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

        return true;
    }
}