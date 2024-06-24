<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */
return [
    // 即时报告
    'requested' => [
        'GET_COUPON_PERFORMANCE_REPORT',
        'GET_PROMOTION_PERFORMANCE_REPORT',

        // Inventory reports
        'GET_FLAT_FILE_OPEN_LISTINGS_DATA',
        'GET_MERCHANT_LISTINGS_ALL_DATA',
        'GET_MERCHANT_LISTINGS_DATA',
        'GET_MERCHANT_LISTINGS_INACTIVE_DATA',
        'GET_MERCHANT_LISTINGS_DATA_BACK_COMPAT',
        'GET_MERCHANT_LISTINGS_DATA_LITE',
        'GET_MERCHANT_LISTINGS_DATA_LITER',
        'GET_MERCHANT_CANCELLED_LISTINGS_DATA',
        'GET_MERCHANTS_LISTINGS_FYP_REPORT',
        'GET_PAN_EU_OFFER_STATUS',
        'GET_MFN_PANEU_OFFER_STATUS',
        'GET_REFERRAL_FEE_PREVIEW_REPORT',

        // Analytics Reports
        'GET_BRAND_ANALYTICS_MARKET_BASKET_REPORT',
        'GET_BRAND_ANALYTICS_SEARCH_TERMS_REPORT',
        'GET_BRAND_ANALYTICS_REPEAT_PURCHASE_REPORT',
        'GET_SALES_AND_TRAFFIC_REPORT', // 销售与流量业务报告 https://github.com/amzn/selling-partner-api-models/blob/7c59ac517edefb3a0b9e7d1b2e3b3ab94ac877bf/schemas/reports/sellerSalesAndTrafficReport.json#L1105
        //        'GET_SALES_AND_TRAFFIC_REPORT_CUSTOM', // 销售与流量业务报告(指定日期范围，用于统计最近3天，7天，14天，30天销量)

        // Order reports
        'GET_FLAT_FILE_ACTIONABLE_ORDER_DATA_SHIPPING', // 请求不了
        'GET_ORDER_REPORT_DATA_INVOICING', // 请求不了
        'GET_ORDER_REPORT_DATA_TAX', // 请求不了
        'GET_ORDER_REPORT_DATA_SHIPPING', // 请求不了
        'GET_FLAT_FILE_ORDER_REPORT_DATA_INVOICING', // 请求不了
        'GET_FLAT_FILE_ORDER_REPORT_DATA_SHIPPING', // 请求不了
        'GET_FLAT_FILE_ORDER_REPORT_DATA_TAX', // 请求不了

        'GET_FLAT_FILE_ALL_ORDERS_DATA_BY_LAST_UPDATE_GENERAL',
        'GET_FLAT_FILE_ALL_ORDERS_DATA_BY_ORDER_DATE_GENERAL',
        'GET_FLAT_FILE_ARCHIVED_ORDERS_DATA_BY_ORDER_DATE',
        'GET_XML_ALL_ORDERS_DATA_BY_LAST_UPDATE_GENERAL',
        'GET_XML_ALL_ORDERS_DATA_BY_ORDER_DATE_GENERAL',
        'GET_FLAT_FILE_PENDING_ORDERS_DATA',
        'GET_PENDING_ORDERS_DATA',
        'GET_CONVERGED_FLAT_FILE_PENDING_ORDERS_DATA',

        // Returns Report
        'GET_XML_RETURNS_DATA_BY_RETURN_DATE',
        'GET_FLAT_FILE_RETURNS_DATA_BY_RETURN_DATE',
        'GET_XML_MFN_PRIME_RETURNS_REPORT',
        'GET_CSV_MFN_PRIME_RETURNS_REPORT',
        'GET_XML_MFN_SKU_RETURN_ATTRIBUTES_REPORT',
        'GET_FLAT_FILE_MFN_SKU_RETURN_ATTRIBUTES_REPORT',

        //         Fulfillment by Amazon (FBA) reports
        'GET_AMAZON_FULFILLED_SHIPMENTS_DATA_GENERAL',
        'GET_AMAZON_FULFILLED_SHIPMENTS_DATA_INVOICING', // EU
        'GET_FLAT_FILE_ALL_ORDERS_DATA_BY_LAST_UPDATE_GENERAL',
        'GET_FLAT_FILE_ALL_ORDERS_DATA_BY_ORDER_DATE_GENERAL',
        'GET_XML_ALL_ORDERS_DATA_BY_LAST_UPDATE_GENERAL',
        'GET_XML_ALL_ORDERS_DATA_BY_ORDER_DATE_GENERAL',
        'GET_FBA_FULFILLMENT_CUSTOMER_SHIPMENT_SALES_DATA',
        'GET_FBA_FULFILLMENT_CUSTOMER_SHIPMENT_PROMOTION_DATA',
        'GET_FBA_FULFILLMENT_CUSTOMER_TAXES_DATA',
        'GET_FBA_STORAGE_FEE_CHARGES_DATA',
        'GET_FBA_FULFILLMENT_LONGTERM_STORAGE_FEE_CHARGES_DATA',
        'GET_FBA_OVERAGE_FEE_CHARGES_DATA',
        'GET_REMOTE_FULFILLMENT_ELIGIBILITY',
        'GET_FBA_FULFILLMENT_INBOUND_NONCOMPLIANCE_DATA',
        'GET_FBA_FULFILLMENT_CUSTOMER_SHIPMENT_REPLACEMENT_DATA',//换货报告
        'GET_FBA_RECOMMENDED_REMOVAL_DATA',//移除订单报告
        'GET_FBA_FULFILLMENT_REMOVAL_SHIPMENT_DETAIL_DATA',//移除货件详情报告
        'GET_AFN_INVENTORY_DATA_BY_COUNTRY',//FOR EU
        'GET_STRANDED_INVENTORY_UI_DATA',

        'GET_RESTOCK_INVENTORY_RECOMMENDATIONS_REPORT', // 补货库存报告
        'GET_AFN_INVENTORY_DATA', // FBA亚马逊完成库存报告
        'GET_FBA_MYI_UNSUPPRESSED_INVENTORY_DATA', // FBA管理库存
        'GET_FBA_INVENTORY_PLANNING_DATA', // FBA管理库存健康报告
        'GET_FBA_ESTIMATED_FBA_FEES_TXT_DATA', // FBA预估费用报告

        'GET_LEDGER_SUMMARY_VIEW_DATA',
        'GET_LEDGER_DETAIL_VIEW_DATA',

        'GET_FBA_FULFILLMENT_CUSTOMER_RETURNS_DATA', // FBA退货报告
        'GET_FBA_REIMBURSEMENTS_DATA', // FBA赔偿报告
        'GET_FBA_FULFILLMENT_REMOVAL_ORDER_DETAIL_DATA', // FBA受损货物明细报告
        'GET_SELLER_FEEDBACK_DATA', // 评估卖方表现的买家的负面和中性反馈（一到三颗星）报告
        'GET_V2_SELLER_PERFORMANCE_REPORT', // 店铺绩效 详见 https://blog.csdn.net/qq594865227/article/details/123263007?spm=1001.2101.3001.6650.5&utm_medium=distribute.pc_relevant.none-task-blog-2%7Edefault%7EBlogCommendFromBaidu%7ERate-5-123263007-blog-117392280.235%5Ev31%5Epc_relevant_default_base3&depth_1-utm_source=distribute.pc_relevant.none-task-blog-2%7Edefault%7EBlogCommendFromBaidu%7ERate-5-123263007-blog-117392280.235%5Ev31%5Epc_relevant_default_base3&utm_relevant_index=6
        'GET_V1_SELLER_PERFORMANCE_REPORT',
        'MARKETPLACE_ASIN_PAGE_VIEW_METRICS',
        'GET_EPR_MONTHLY_REPORTS',
        'GET_EPR_QUARTERLY_REPORTS',
        'GET_EPR_ANNUAL_REPORTS',
        'GET_VENDOR_REAL_TIME_INVENTORY_REPORT',
    ],
    // 周期报告
    'scheduled' => [
        'GET_V2_SETTLEMENT_REPORT_DATA_FLAT_FILE_V2', // 付款报告
        'GET_DATE_RANGE_FINANCIAL_TRANSACTION_DATA', // 日期范围报告
    ],
];
