<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Util\Amazon\Report;

use function Hyperf\Support\make;

class ReportFactory
{
    /**
     * @param int $merchant_id
     * @param int $merchant_store_id
     * @param string $region
     * @param string $report_type
     * @return ReportBase
     */
    public static function getInstance(int $merchant_id, int $merchant_store_id, string $region, string $report_type): ReportBase
    {
        $class = match ($report_type) {

            'GET_COUPON_PERFORMANCE_REPORT' => CouponPerformanceReport::class,//优惠券报告
            'GET_PROMOTION_PERFORMANCE_REPORT' => PromotionPerformanceReport::class,//促销报告
            // Inventory reports
            'GET_FLAT_FILE_OPEN_LISTINGS_DATA' => FlatFileOpenListingsDataReport::class,
            'GET_MERCHANT_LISTINGS_ALL_DATA' => MerchantListingsAllDataReport::class,
            'GET_MERCHANT_LISTINGS_DATA' => MerchantListingsDataReport::class,
            'GET_MERCHANT_LISTINGS_INACTIVE_DATA' => MerchantListingsInactiveDataReport::class,
            'GET_MERCHANT_LISTINGS_DATA_BACK_COMPAT' => MerchantListingsDataBackCompatReport::class,
            'GET_MERCHANT_LISTINGS_DATA_LITE' => MerchantListingsDataLiteReport::class,
            'GET_MERCHANT_LISTINGS_DATA_LITER' => MerchantListingsDataLiterReport::class,
            'GET_MERCHANT_CANCELLED_LISTINGS_DATA' => MerchantCancelledListingsDataReport::class,
            'GET_MERCHANTS_LISTINGS_FYP_REPORT' => MerchantListingsFypReport::class,
            'GET_REFERRAL_FEE_PREVIEW_REPORT' => ReferralFeePreviewReport::class,
            // Analytics Reports
            'GET_BRAND_ANALYTICS_MARKET_BASKET_REPORT' => BrandAnalyticsMarketBasketReport::class,//市场采购行为分析报告
            'GET_BRAND_ANALYTICS_SEARCH_TERMS_REPORT' => BrandAnalyticsSearchTermsReport::class,// 亚马逊搜索词报告
            'GET_BRAND_ANALYTICS_REPEAT_PURCHASE_REPORT' => BrandAnalyticsRepeatPurchaseReport::class,// 重复购买
            'GET_SALES_AND_TRAFFIC_REPORT' => SalesAndTrafficReport::class,//销售与流量报告
            'GET_SALES_AND_TRAFFIC_REPORT_CUSTOM' => SalesAndTrafficReportCustom::class,//销售与流量报告(计算最近x天的平均销量，实际还是GET_SALES_AND_TRAFFIC_REPORT_CUSTOM报告)
            // Order reports
            'GET_FLAT_FILE_ACTIONABLE_ORDER_DATA_SHIPPING' => FlatFileActionableOrderDataShippingReport::class,// 获取报告失败
            'GET_ORDER_REPORT_DATA_INVOICING' => OrderReportDataInvoicingReport::class,// 获取报告失败 -- 适用欧洲
            'GET_ORDER_REPORT_DATA_TAX' => OrderReportDataTaxReport::class,// 获取报告失败 -- 适用北美
            'GET_ORDER_REPORT_DATA_SHIPPING' => OrderReportDataShippingReport::class,
            'GET_FLAT_FILE_ORDER_REPORT_DATA_INVOICING' => FlatFileOrderReportDataInvoicingReport::class,
            'GET_FLAT_FILE_ORDER_REPORT_DATA_SHIPPING' => FlatFileOrderReportDataShippingReport::class,
            'GET_FLAT_FILE_ORDER_REPORT_DATA_TAX' => FlatFileOrderReportDataTaxReport::class,
            'GET_FLAT_FILE_ALL_ORDERS_DATA_BY_LAST_UPDATE_GENERAL' => FlatFileAllOrdersDataByLastUpdateGeneralReport::class,
            'GET_FLAT_FILE_ALL_ORDERS_DATA_BY_ORDER_DATE_GENERAL' => FlatFileAllOrdersDataByOrderDateGeneralReport::class,
            'GET_FLAT_FILE_ARCHIVED_ORDERS_DATA_BY_ORDER_DATE' => FlatFileArchivedOrdersDataByOrderDateReport::class,
            'GET_XML_ALL_ORDERS_DATA_BY_LAST_UPDATE_GENERAL' => XmlAllOrdersDataByLastUpdateGeneralReport::class,
            'GET_XML_ALL_ORDERS_DATA_BY_ORDER_DATE_GENERAL' => XmlAllOrdersDataByOrderDateReport::class,
            'GET_FLAT_FILE_PENDING_ORDERS_DATA' => FlatFilePendingOrdersDataReport::class,
            'GET_PENDING_ORDERS_DATA' => PendingOrdersDataReport::class,
            'GET_CONVERGED_FLAT_FILE_PENDING_ORDERS_DATA' => ConvergedFlatFilePendingOrdersDataReport::class,
            // Returns Report
            'GET_XML_RETURNS_DATA_BY_RETURN_DATE' => XmlReturnsDataByReturnDateReport::class,
            'GET_FLAT_FILE_RETURNS_DATA_BY_RETURN_DATE' => FlatFileReturnsDataByReturnDateReport::class,
            'GET_XML_MFN_PRIME_RETURNS_REPORT' => XmlMfnPrimeReturnsReport::class,
            'GET_CSV_MFN_PRIME_RETURNS_REPORT' => CsvMfnPrimeReturnsReport::class,
            'GET_XML_MFN_SKU_RETURN_ATTRIBUTES_REPORT' => XmlMfnSkuReturnAttributesReport::class,
            'GET_FLAT_FILE_MFN_SKU_RETURN_ATTRIBUTES_REPORT' => FlatFileMfnSkuReturnAttributesReport::class,
            // Fulfillment by Amazon (FBA) reports
            'GET_AMAZON_FULFILLED_SHIPMENTS_DATA_GENERAL' => AmazonFulfilledShipmentsDataGeneralReport::class,
            'GET_AMAZON_FULFILLED_SHIPMENTS_DATA_INVOICING',
                //        'GET_FLAT_FILE_ALL_ORDERS_DATA_BY_LAST_UPDATE_GENERAL',
                //        'GET_FLAT_FILE_ALL_ORDERS_DATA_BY_ORDER_DATE_GENERAL',
                //        'GET_XML_ALL_ORDERS_DATA_BY_LAST_UPDATE_GENERAL',
                //        'GET_XML_ALL_ORDERS_DATA_BY_ORDER_DATE_GENERAL',
            'GET_FBA_FULFILLMENT_CUSTOMER_SHIPMENT_SALES_DATA',
            'GET_FBA_FULFILLMENT_CUSTOMER_SHIPMENT_PROMOTION_DATA',
            'GET_FBA_FULFILLMENT_CUSTOMER_TAXES_DATA',
            'GET_FBA_STORAGE_FEE_CHARGES_DATA' => FbaStorageFeeChargesDataReport::class,
            'GET_FBA_FULFILLMENT_LONGTERM_STORAGE_FEE_CHARGES_DATA' => FbaFulfillmentLongTermStorageFeeChargesDataReport::class,
            'GET_FBA_OVERAGE_FEE_CHARGES_DATA' => FbaOverageFeeChargesDataReport::class,
            'GET_REMOTE_FULFILLMENT_ELIGIBILITY',

            'GET_RESTOCK_INVENTORY_RECOMMENDATIONS_REPORT' => RestockInventoryRecommendationsReport::class,
            'GET_AFN_INVENTORY_DATA' => AfnInventoryDataReport::class,
            'GET_FBA_MYI_UNSUPPRESSED_INVENTORY_DATA' => FbaMyiUnsuppressedInventoryDataReport::class,
            'GET_FBA_INVENTORY_PLANNING_DATA' => FbaInventoryPlanningDataReport::class,
            'GET_SELLER_FEEDBACK_DATA' => FeedbackDataReport::class,
            'GET_FBA_ESTIMATED_FBA_FEES_TXT_DATA' => FbaEstimatedFeeTxtDataReport::class,
            'GET_FBA_FULFILLMENT_CUSTOMER_RETURNS_DATA' => FbaFulfillmentCustomerReturnsData::class,
            'GET_FBA_REIMBURSEMENTS_DATA' => FbaReimbursementsData::class,
            'GET_FBA_FULFILLMENT_REMOVAL_ORDER_DETAIL_DATA' => FbaFulfillmentRemovalOrderDetailData::class,
            'GET_V2_SETTLEMENT_REPORT_DATA_FLAT_FILE' => V2SettlementReportDataFlatFile::class,
            'GET_V2_SETTLEMENT_REPORT_DATA_FLAT_FILE_V2' => V2SettlementReportDataFlatFileV2::class,
            'GET_V2_SELLER_PERFORMANCE_REPORT' => V2SellerPerformanceReport::class,
            'GET_DATE_RANGE_FINANCIAL_TRANSACTION_DATA' => DateRangeFinancialTransactionDataReport::class,
            'GET_LEDGER_SUMMARY_VIEW_DATA' => FbaLedgerSummaryViewDataReport::class,
            'GET_LEDGER_DETAIL_VIEW_DATA' => FbaLedgerDetailViewDataReport::class,
            default => throw new \RuntimeException(sprintf('请定义%s报告处理类', $report_type)),
        };

        return make($class, [$merchant_id, $merchant_store_id, $region, $report_type]);
    }
}
