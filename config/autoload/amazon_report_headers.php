<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */
use AmazonPHP\SellingPartner\Marketplace;
use AmazonPHP\SellingPartner\Regions;

return [
    'GET_COUPON_PERFORMANCE_REPORT' => [],
    'GET_PROMOTION_PERFORMANCE_REPORT' => [],

    // Inventory reports
    'GET_FLAT_FILE_OPEN_LISTINGS_DATA' => [],
    'GET_MERCHANT_LISTINGS_ALL_DATA' => [
        'item-name' => 'item_name',
        'item-description' => 'item_description',
        'listing-id' => 'listing_id',
        'seller-sku' => 'seller_sku',
        'price' => 'price',
        'quantity' => 'quantity',
        'open-date' => 'open_date',
        'image-url' => 'image_url',
        'item-is-marketplace' => 'item_is_marketplace',
        'product-id-type' => 'product_id_type',
        'zshop-shipping-fee' => 'zshop_shipping_fee',
        'item-note' => 'item_note',
        'item-condition' => 'item_condition',
        'zshop-category1' => 'zshop_category1',
        'zshop-browse-path' => 'zshop_browse_path',
        'zshop-storefront-feature' => 'zshop_storefront_feature',
        'asin1' => 'asin1',
        'asin2' => 'asin2',
        'asin3' => 'asin3',
        'will-ship-internationally' => 'will_ship_internationally',
        'expedited-shipping' => 'expedited_shipping',
        'zshop-boldface' => 'zshop_boldface',
        'product-id' => 'product_id',
        'bid-for-featured-placement' => 'bid_for_featured_placement',
        'add-delete' => 'add_delete',
        'pending-quantity' => 'pending_quantity',
        'fulfillment-channel' => 'fulfillment_channel',
        'merchant-shipping-group' => 'merchant_shipping_group',
        'status' => 'status',
    ],
    'GET_MERCHANT_LISTINGS_DATA' => [],
    'GET_MERCHANT_LISTINGS_INACTIVE_DATA' => [],
    'GET_MERCHANT_LISTINGS_DATA_BACK_COMPAT' => [],
    'GET_MERCHANT_LISTINGS_DATA_LITE' => [],
    'GET_MERCHANT_LISTINGS_DATA_LITER' => [],
    'GET_MERCHANT_CANCELLED_LISTINGS_DATA' => [],
    'GET_MERCHANTS_LISTINGS_FYP_REPORT' => [],
    // Analytics Reports
    'GET_BRAND_ANALYTICS_MARKET_BASKET_REPORT' => [],
    'GET_BRAND_ANALYTICS_SEARCH_TERMS_REPORT' => [],
    'GET_BRAND_ANALYTICS_REPEAT_PURCHASE_REPORT' => [],
    'GET_SALES_AND_TRAFFIC_REPORT' => [],
    'GET_SALES_AND_TRAFFIC_REPORT_CUSTOM' => [], // 详细解释看类

    // Order reports
    'GET_FLAT_FILE_ACTIONABLE_ORDER_DATA_SHIPPING' => [],
    'GET_ORDER_REPORT_DATA_INVOICING' => [],
    'GET_ORDER_REPORT_DATA_TAX' => [],
    'GET_ORDER_REPORT_DATA_SHIPPING' => [],
    'GET_FLAT_FILE_ORDER_REPORT_DATA_INVOICING' => [],
    'GET_FLAT_FILE_ORDER_REPORT_DATA_SHIPPING' => [],
    'GET_FLAT_FILE_ORDER_REPORT_DATA_TAX' => [],
    'GET_FLAT_FILE_ALL_ORDERS_DATA_BY_LAST_UPDATE_GENERAL' => [],
    'GET_FLAT_FILE_ALL_ORDERS_DATA_BY_ORDER_DATE_GENERAL' => [],
    'GET_FLAT_FILE_ARCHIVED_ORDERS_DATA_BY_ORDER_DATE' => [],
    'GET_XML_ALL_ORDERS_DATA_BY_LAST_UPDATE_GENERAL' => [],
    'GET_XML_ALL_ORDERS_DATA_BY_ORDER_DATE_GENERAL' => [],
    'GET_FLAT_FILE_PENDING_ORDERS_DATA' => [],
    'GET_PENDING_ORDERS_DATA' => [],
    'GET_CONVERGED_FLAT_FILE_PENDING_ORDERS_DATA' => [],
    // Returns Report
    'GET_XML_RETURNS_DATA_BY_RETURN_DATE' => [],
    'GET_FLAT_FILE_RETURNS_DATA_BY_RETURN_DATE' => [],
    'GET_XML_MFN_PRIME_RETURNS_REPORT' => [],
    'GET_CSV_MFN_PRIME_RETURNS_REPORT' => [],
    'GET_XML_MFN_SKU_RETURN_ATTRIBUTES_REPORT' => [],
    'GET_FLAT_FILE_MFN_SKU_RETURN_ATTRIBUTES_REPORT' => [],
    // Fulfillment by Amazon (FBA) reports
    'GET_AMAZON_FULFILLED_SHIPMENTS_DATA_GENERAL' => [
        'amazon-order-id' => 'amazon_order_id',
        'merchant-order-id' => 'merchant_order_id',
        'shipment-id' => 'shipment_id',
        'shipment-item-id' => 'shipment_item_id',
        'amazon-order-item-id' => 'amazon_order_item_id',
        'merchant-order-item-id' => 'merchant_order_item_id',
        'purchase-date' => 'purchase_date',
        'payments-date' => 'payments_date',
        'shipment-date' => 'shipment_date',
        'reporting-date' => 'reporting_date',
        'buyer-email' => 'buyer_email',
        'buyer-name' => 'buyer_name',
        'buyer-phone-number' => 'buyer_phone_number',
        'sku' => 'sku',
        'product-name' => 'product_name',
        'quantity-shipped' => 'quantity_shipped',
        'currency' => 'currency',
        'item-price' => 'item_price',
        'item-tax' => 'item_tax',
        'shipping-price' => 'shipping_price',
        'shipping-tax' => 'shipping_tax',
        'gift-wrap-price' => 'gift_wrap_price',
        'gift-wrap-tax' => 'gift_wrap_tax',
        'ship-service-level' => 'ship_service_level',
        'recipient-name' => 'recipient_name',
        'ship-address-1' => 'ship_address_1',
        'ship-address-2' => 'ship_address_2',
        'ship-address-3' => 'ship_address_3',
        'ship-city' => 'ship_city',
        'ship-state' => 'ship_state',
        'ship-postal-code' => 'ship_postal_code',
        'ship-country' => 'ship_country',
        'ship-phone-number' => 'ship_phone_number',
        'bill-address-1' => 'bill_address_1',
        'bill-address-2' => 'bill_address_2',
        'bill-address-3' => 'bill_address_3',
        'bill-city' => 'bill_city',
        'bill-state' => 'bill_state',
        'bill-postal-code' => 'bill_postal_code',
        'bill-country' => 'bill_country',
        'item-promotion-discount' => 'item_promotion_discount',
        'ship-promotion-discount' => 'ship_promotion_discount',
        'carrier' => 'carrier',
        'tracking-number' => 'tracking_number',
        'estimated-arrival-date' => 'estimated_arrival_date',
        'fulfillment-center-id' => 'fulfillment_center_id',
        'fulfillment-channel' => 'fulfillment_channel',
        'sales-channel' => 'sales_channel',
    ],
    'GET_AMAZON_FULFILLED_SHIPMENTS_DATA_INVOICING' => [],
    'GET_AMAZON_FULFILLED_SHIPMENTS_DATA_TAX' => [],
    //    'GET_FLAT_FILE_ALL_ORDERS_DATA_BY_LAST_UPDATE_GENERAL' => [],
    //    'GET_FLAT_FILE_ALL_ORDERS_DATA_BY_ORDER_DATE_GENERAL' => [],
    //    'GET_XML_ALL_ORDERS_DATA_BY_LAST_UPDATE_GENERAL' => [],
    //    'GET_XML_ALL_ORDERS_DATA_BY_ORDER_DATE_GENERAL' => [],
    'GET_FBA_FULFILLMENT_CUSTOMER_SHIPMENT_SALES_DATA' => [],
    'GET_FBA_FULFILLMENT_CUSTOMER_SHIPMENT_PROMOTION_DATA' => [],
    'GET_FBA_FULFILLMENT_CUSTOMER_TAXES_DATA' => [],
    'GET_FBA_STORAGE_FEE_CHARGES_DATA' => [
        Regions::NORTH_AMERICA => [
            'asin' => 'asin',
            'fnsku' => 'fnsku',
            'product_name' => 'product_name',
            'fulfillment_center' => 'fulfillment_center',
            'country_code' => 'country_code',
            'longest_side' => 'longest_side',
            'median_side' => 'median_side',
            'shortest_side' => 'shortest_side',
            'measurement_units' => 'measurement_units',
            'weight' => 'weight',
            'weight_units' => 'weight_units',
            'item_volume' => 'item_volume',
            'volume_units' => 'volume_units',
            'average_quantity_on_hand' => 'average_quantity_on_hand',
            'average_quantity_pending_removal' => 'average_quantity_pending_removal',
            'estimated_total_item_volume' => 'estimated_total_item_volume',
            'month_of_charge' => 'month_of_charge',
            'storage_utilization_ratio' => 'storage_utilization_ratio',
            'storage_utilization_ratio_units' => 'storage_utilization_ratio_units',
            'base_rate' => 'base_rate',
            'utilization_surcharge_rate' => 'utilization_surcharge_rate',
            'currency' => 'currency',
            'estimated_monthly_storage_fee' => 'estimated_monthly_storage_fee',
            'total_incentive_fee_amount' => 'total_incentive_fee_amount',
            'breakdown_incentive_fee_amount' => 'breakdown_incentive_fee_amount',
            'average_quantity_customer_orders' => 'average_quantity_customer_orders',
            'dangerous_goods_storage_type' => 'dangerous_goods_storage_type',
            'product_size_tier' => 'product_size_tier',
            'eligible_for_inventory_discount' => 'eligible_for_inventory_discount',
            'qualifies_for_inventory_discount' => 'qualifies_for_inventory_discount',
        ],
        Regions::EUROPE => [
            'asin' => 'asin',
            'fnsku' => 'fnsku',
            'product_name' => 'product_name',
            'fulfillment_center' => 'fulfillment_center',
            'country_code' => 'country_code',
            'longest_side' => 'longest_side',
            'median_side' => 'median_side',
            'shortest_side' => 'shortest_side',
            'measurement_units' => 'measurement_units',
            'weight' => 'weight',
            'weight_units' => 'weight_units',
            'item_volume' => 'item_volume',
            'volume_units' => 'volume_units',
            'average_quantity_on_hand' => 'average_quantity_on_hand',
            'average_quantity_pending_removal' => 'average_quantity_pending_removal',
            'estimated_total_item_volume' => 'estimated_total_item_volume',
            'month_of_charge' => 'month_of_charge',
            'storage_utilization_ratio' => 'storage_utilization_ratio',
            'storage_utilization_ratio_units' => 'storage_utilization_ratio_units',
            'base_rate' => 'base_rate',
            'utilization_surcharge_rate' => 'utilization_surcharge_rate',
            'currency' => 'currency',
            'estimated_monthly_storage_fee' => 'estimated_monthly_storage_fee',
            'total_incentive_fee_amount' => 'total_incentive_fee_amount',
            'breakdown_incentive_fee_amount' => 'breakdown_incentive_fee_amount',
            'average_quantity_customer_orders' => 'average_quantity_customer_orders',
            'category' => 'dangerous_goods_storage_type',
            'product_size_tier' => 'product_size_tier',
            'eligible_for_inventory_discount' => 'eligible_for_inventory_discount',
            'qualifies_for_inventory_discount' => 'qualifies_for_inventory_discount',
        ],
    ],
    'GET_FBA_FULFILLMENT_LONGTERM_STORAGE_FEE_CHARGES_DATA' => [
        'snapshot-date' => 'snapshot_date',
        'sku' => 'sku',
        'fnsku' => 'fnsku',
        'asin' => 'asin',
        'product-name' => 'product_name',
        'condition' => 'condition',
        'per-unit-volume' => 'per_unit_volume',
        'currency' => 'currency',
        'volume-unit' => 'volume_unit',
        'country' => 'country',
        'qty-charged' => 'qty_charged',
        'amount-charged' => 'amount_charged',
        'surcharge-age-tier' => 'surcharge_age_tier',
        'rate-surcharge' => 'rate_surcharge',
    ],
    'GET_FBA_OVERAGE_FEE_CHARGES_DATA' => [],
    'GET_REMOTE_FULFILLMENT_ELIGIBILITY' => [],
    'GET_FBA_FULFILLMENT_INBOUND_NONCOMPLIANCE_DATA' => [],

    'GET_RESTOCK_INVENTORY_RECOMMENDATIONS_REPORT' => [
        'Country' => 'country',
        'Product Name' => 'product_name',
        'FNSKU' => 'fnsku',
        'Merchant SKU' => 'merchant_sku',
        'ASIN' => 'asin',
        'Condition' => 'condition',
        'Supplier' => 'supplier',
        'Supplier part no.' => 'supplier_part_num',
        'Currency code' => 'currency_code',
        'Price' => 'price',
        'Sales last 30 days' => 'sales_last_30_days',
        'Units Sold Last 30 Days' => 'units_sold_last_30_days',
        'Total Units' => 'total_units',
        'Inbound' => 'inbound',
        'Available' => 'available',
        'FC transfer' => 'fc_transfer',
        'FC Processing' => 'fc_processing',
        'Customer Order' => 'customer_order',
        'Unfulfillable' => 'unfulfillable',
        'Working' => 'working',
        'Shipped' => 'shipped',
        'Receiving' => 'receiving',
        'Fulfilled by' => 'fulfilled_by',
        'Total Days of Supply (including units from open shipments)' => 'total_days_of_supply',
        'Days of Supply at Amazon Fulfillment Network' => 'days_of_supply_at_amazon_fulfillment_network',
        'Alert' => 'alert',
        'Recommended replenishment qty' => 'recommended_replenishment_qty',
        'Recommended ship date' => 'recommended_ship_date',
        'Recommended action' => 'recommended_action',
        'Unit storage size' => 'unit_storage_size',
    ],
    'GET_AFN_INVENTORY_DATA' => [
        'seller-sku' => 'seller_sku',
        'fulfillment-channel-sku' => 'fulfillment_channel_sku',
        'asin' => 'asin',
        'condition-type' => 'condition_type',
        'Warehouse-Condition-code' => 'warehouse_condition_code',
        'Quantity Available' => 'quantity_available',
    ],
    'GET_FBA_MYI_UNSUPPRESSED_INVENTORY_DATA' => [
        'sku' => 'sku',
        'fnsku' => 'fnsku',
        'asin' => 'asin',
        'product-name' => 'product_name',
        'condition' => 'condition',
        'your-price' => 'your_price',
        'mfn-listing-exists' => 'mfn_listing_exists',
        'mfn-fulfillable-quantity' => 'mfn_fulfillable_quantity',
        'afn-listing-exists' => 'afn_listing_exists',
        'afn-warehouse-quantity' => 'afn_warehouse_quantity',
        'afn-fulfillable-quantity' => 'afn_fulfillable_quantity',
        'afn-unsellable-quantity' => 'afn_unsellable_quantity',
        'afn-reserved-quantity' => 'afn_reserved_quantity',
        'afn-total-quantity' => 'afn_total_quantity',
        'per-unit-volume' => 'per_unit_volume',
        'afn-inbound-working-quantity' => 'afn_inbound_working_quantity',
        'afn-inbound-shipped-quantity' => 'afn_inbound_shipped_quantity',
        'afn-inbound-receiving-quantity' => 'afn_inbound_receiving_quantity',
        'afn-researching-quantity' => 'afn_researching_quantity',
        'afn-reserved-future-supply' => 'afn_reserved_future_supply',
        'afn-future-supply-buyable' => 'afn_future_supply_buyable',
    ],
    'GET_FBA_INVENTORY_PLANNING_DATA' => [
        'snapshot-date' => 'snapshot_date',
        'sku' => 'sku',
        'fnsku' => 'fnsku',
        'asin' => 'asin',
        'product-name' => 'product_name',
        'condition' => 'condition',
        'available' => 'available',
        'pending-removal-quantity' => 'pending_removal_quantity',
        'inv-age-0-to-90-days' => 'inv_age_0_to_90_days',
        'inv-age-91-to-180-days' => 'inv_age_91_to_180_days',
        'inv-age-181-to-270-days' => 'inv_age_181_to_270_days',
        'inv-age-271-to-365-days' => 'inv_age_271_to_365_days',
        'inv-age-365-plus-days' => 'inv_age_365_plus_days',
        'currency' => 'currency',
        'qty-to-be-charged-ltsf-6-mo' => 'qty_to_be_charged_ltsf_6-mo',
        'projected-ltsf-6-mo' => 'projected_ltsf_6_mo',
        'qty-to-be-charged-ltsf-9-mo' => 'qty_to_be_charged_ltsf_9_mo',
        'projected-ltsf-9-mo' => 'projected_ltsf_9_mo',
        'qty-to-be-charged-ltsf-12-mo' => 'qty_to_be_charged_ltsf_12_mo',
        'estimated-ltsf-next-charge' => 'estimated_ltsf_next_charge',
        'units-shipped-t7' => 'units_shipped_t7',
        'units-shipped-t30' => 'units_shipped_t30',
        'units-shipped-t60' => 'units_shipped_t60',
        'units-shipped-t90' => 'units_shipped_t90',
        'alert' => 'alert',
        'your-price' => 'your_price',
        'sales-price' => 'sales_price',
        'lowest-price-new-plus-shipping' => 'lowest_price_new_plus_shipping',
        'lowest-price-used' => 'lowest_price_used',
        'recommended-action' => 'recommended_action',
        'healthy-inventory-level' => 'healthy_inventory_level',
        'recommended-sales-price' => 'recommended_sales_price',
        'recommended-sale-duration-days' => 'recommended_sale_duration_days',
        'recommended-removal-quantity' => 'recommended_removal_quantity',
        'estimated-cost-savings-of-recommended-actions' => 'estimated_cost_savings_of_recommended_actions',
        'sell-through' => 'sell_through',
        'item-volume' => 'item_volume',
        'volume-unit-measurement' => 'volume_unit_measurement',
        'storage-type' => 'storage_type',
        'storage-volume' => 'storage_volume',
        'marketplace' => 'marketplace',
        'product-group' => 'product_group',
        'sales-rank' => 'sales_rank',
        'days-of-supply' => 'days_of_supply',
        'estimated-excess-quantity' => 'estimated_excess_quantity',
        'weeks-of-cover-t30' => 'weeks_of_cover_t30',
        'weeks-of-cover-t90' => 'weeks_of_cover_t90',
        'featuredoffer-price' => 'featuredoffer_price',
        'sales-shipped-last-7-days' => 'sales_shipped_last_7_days',
        'sales-shipped-last-30-days' => 'sales_shipped_last_30_days',
        'sales-shipped-last-60-days' => 'sales_shipped_last_60_days',
        'sales-shipped-last-90-days' => 'sales_shipped_last_90_days',
        'inv-age-0-to-30-days' => 'inv_age_0_to_30_days',
        'inv-age-31-to-60-days' => 'inv_age_31_to_60_days',
        'inv-age-61-to-90-days' => 'inv_age_61_to_90_days',
        'inv-age-181-to-330-days' => 'inv_age_181_to_330_days',
        'inv-age-331-to-365-days' => 'inv_age_331_to_365_days',
        'estimated-storage-cost-next-month' => 'estimated_storage_cost_next_month',
        'inbound-quantity' => 'inbound_quantity',
        'inbound-working' => 'inbound_working',
        'inbound-shipped' => 'inbound_shipped',
        'inbound-received' => 'inbound_received',
        'no-sale-last-6-months' => 'no_sale_last_6_months',
        'reserved-quantity' => 'reserved_quantity',
        'unfulfillable-quantity' => 'unfulfillable_quantity',
        'quantity-to-be-charged-ais-181-210-days' => 'quantity_to_be_charged_ais_181_210_days',
        'estimated-ais-181-210-days' => 'estimated_ais_181_210_days',
        'quantity-to-be-charged-ais-211-240-days' => 'quantity_to_be_charged_ais_211_240_days',
        'estimated-ais-211-240-days' => 'estimated_ais_211_240_days',
        'quantity-to-be-charged-ais-241-270-days' => 'quantity_to_be_charged_ais_241_270_days',
        'estimated-ais-241-270-days' => 'estimated_ais_241_270_days',
        'quantity-to-be-charged-ais-271-300-days' => 'quantity_to_be_charged_ais_271_300_days',
        'estimated-ais-271-300-days' => 'estimated_ais_271_300_days',
        'quantity-to-be-charged-ais-301-330-days' => 'quantity_to_be_charged_ais_301_330_days',
        'estimated-ais-301-330-days' => 'estimated_ais_301_330_days',
        'quantity-to-be-charged-ais-331-365-days' => 'quantity_to_be_charged_ais_331_365_days',
        'estimated-ais-331-365-days' => 'estimated_ais_331_365_days',
        'quantity-to-be-charged-ais-365-PLUS-days' => 'quantity_to_be_charged_ais_365_plus_days',
        'estimated-ais-365-plus-days' => 'estimated_ais_365_plus_days',
    ],
    'GET_FBA_ESTIMATED_FBA_FEES_TXT_DATA' => [
        'sku' => 'sku',
        'fnsku' => 'fn_sku',
        'asin' => 'asin',
        'product-name' => 'product_name',
        'product-group' => 'product_group',
        'brand' => 'brand',
        'fulfilled-by' => 'fulfilled_by',
        'your-price' => 'your_price',
        'sales-price' => 'sales_price',
        'longest-side' => 'longest_side',
        'median-side' => 'median_side',
        'shortest-side' => 'shortest_side',
        'length-and-girth' => 'length_and_girth',
        'unit-of-dimension' => 'unit_of_dimension',
        'item-package-weight' => 'item_package_weight',
        'unit-of-weight' => 'unit_of_weight',
        'product-size-tier' => 'product_size_tier',
        'currency' => 'currency',
        'estimated-fee-total' => 'estimated_fee_total',
        'estimated-referral-fee-per-unit' => 'estimated_referral_fee_per_unit',
        'estimated-variable-closing-fee' => 'estimated_variable_closing_fee',
        'estimated-order-handling-fee-per-order' => 'estimated_order_handing_fee_per_order',
        'estimated-pick-pack-fee-per-unit' => 'estimated_pick_pack_fee_per_unit',
        'estimated-weight-handling-fee-per-unit' => 'estimated_weight_handling_fee_per_unit',
        'expected-fulfillment-fee-per-unit' => 'expected_fulfillment_fee_per_unit',
    ],
    'GET_FBA_FULFILLMENT_CUSTOMER_RETURNS_DATA' => [
        'return-date' => 'return_date',
        'order-id' => 'order_id',
        'sku' => 'sku',
        'asin' => 'asin',
        'fnsku' => 'fnsku',
        'product-name' => 'product_name',
        'quantity' => 'quantity',
        'fulfillment-center-id' => 'fulfillment_center_id',
        'detailed-disposition' => 'detailed_disposition',
        'reason' => 'reason',
        'status' => 'status',
        'license-plate-number' => 'license_plate_number',
        'customer-comments' => 'customer_comments',
    ],
    'GET_FBA_REIMBURSEMENTS_DATA' => [
        'approval-date' => 'approval_date',
        'reimbursement-id' => 'reimbursement_id',
        'case-id' => 'case_id',
        'amazon-order-id' => 'amazon_order_id',
        'reason' => 'reason',
        'sku' => 'sku',
        'fnsku' => 'fnsku',
        'asin' => 'asin',
        'product-name' => 'product_name',
        'condition' => 'condition',
        'currency-unit' => 'currency_unit',
        'amount-per-unit' => 'amount_per_unit',
        'amount-total' => 'amount_total',
        'quantity-reimbursed-cash' => 'quantity_reimbursed_cash',
        'quantity-reimbursed-inventory' => 'quantity_reimbursed_inventory',
        'quantity-reimbursed-total' => 'quantity_reimbursed_total',
        'original-reimbursement-id' => 'original_reimbursement_id',
        'original-reimbursement-type' => 'original_reimbursement_type',
    ],
    'GET_FBA_FULFILLMENT_REMOVAL_ORDER_DETAIL_DATA' => [
        'request-date' => 'request_date',
        'order-id' => 'order_id',
        'order-type' => 'order_type',
        'order-status' => 'order_status',
        'last-updated-date' => 'last_updated_date',
        'sku' => 'sku',
        'fnsku' => 'fnsku',
        'disposition' => 'disposition',
        'requested-quantity' => 'requested_quantity',
        'cancelled-quantity' => 'cancelled_quantity',
        'disposed-quantity' => 'disposed_quantity',
        'shipped-quantity' => 'shipped_quantity',
        'in-process-quantity' => 'in_process_quantity',
        'removal-fee' => 'removal_fee',
        'currency' => 'currency',
    ],
    'GET_SELLER_FEEDBACK_DATA' => [
        'Date' => 'date',
        'Rating' => 'rating',
        'Comments' => 'comments',
        'Response' => 'response',
        'Order ID' => 'order_id',
        'Rater Email' => 'rater_email',
    ],
    'GET_V2_SELLER_PERFORMANCE_REPORT' => [],
    'GET_V1_SELLER_PERFORMANCE_REPORT' => [],
    'GET_REFERRAL_FEE_PREVIEW_REPORT' => [],
    'GET_V2_SETTLEMENT_REPORT_DATA_FLAT_FILE_V2' => [
        'settlement-id' => 'settlement_id',
        'settlement-start-date' => 'settlement_start_date',
        'settlement-end-date' => 'settlement_end_date',
        'deposit-date' => 'deposit_date',
        'total-amount' => 'total_amount',
        'currency' => 'currency',
        'transaction-type' => 'transaction_type',
        'order-id' => 'order_id',
        'merchant-order-id' => 'merchant_order_id',
        'adjustment-id' => 'adjustment_id',
        'shipment-id' => 'shipment_id',
        'marketplace-name' => 'marketplace_name',
        'amount-type' => 'amount_type',
        'amount-description' => 'amount_description',
        'amount' => 'amount',
        'fulfillment-id' => 'fulfillment_id',
        'posted-date' => 'posted_date',
        'posted-date-time' => 'posted_date_time',
        'order-item-code' => 'order_item_code',
        'merchant-order-item-id' => 'merchant_order_item_id',
        'merchant-adjustment-item-id' => 'merchant_adjustment_item_id',
        'sku' => 'sku',
        'quantity-purchased' => 'quantity_purchased',
        'promotion-id' => 'promotion_id',
    ],
    'GET_DATE_RANGE_FINANCIAL_TRANSACTION_DATA' => [
        Marketplace::US()->countryCode() => [
            'date/time' => 'date',
            'settlement id' => 'settlement_id',
            'type' => 'type',
            'order id' => 'order_id',
            'sku' => 'sku',
            'description' => 'description',
            'quantity' => 'quantity',
            'marketplace' => 'marketplace',
            'account type' => 'account_type',
            'fulfillment' => 'fulfillment',
            'order city' => 'order_city',
            'order state' => 'order_state',
            'order postal' => 'order_postal',
            'tax collection model' => 'tax_collection_model',
            'product sales' => 'product_sales',
            'product sales tax' => 'product_sales_tax',
            'shipping credits' => 'shipping_credits',
            'shipping credits tax' => 'shipping_credits_tax',
            'gift wrap credits' => 'gift_wrap_credits',
            'giftwrap credits tax' => 'gift_wrap_credits_tax',
            'Regulatory Fee' => 'regulatory_fee',
            'Tax On Regulatory Fee' => 'tax_on_regulatory_fee',
            'promotional rebates' => 'promotional_rebates',
            'promotional rebates tax' => 'promotional_rebates_tax',
            'marketplace withheld tax' => 'marketplace_withheld_tax',
            'selling fees' => 'selling_fees',
            'fba fees' => 'fba_fees',
            'other transaction fees' => 'other_transaction_fees',
            'other' => 'other',
            'total' => 'total',
        ],
        Marketplace::CA()->countryCode() => [
            'date/time' => 'date',
            'settlement id' => 'settlement_id',
            'type' => 'type',
            'order id' => 'order_id',
            'sku' => 'sku',
            'description' => 'description',
            'quantity' => 'quantity',
            'marketplace' => 'marketplace',
            //            '' => 'account_type',//CA地区报告没有该表头数据
            'fulfillment' => 'fulfillment',
            'order city' => 'order_city',
            'order state' => 'order_state',
            'order postal' => 'order_postal',
            'tax collection model' => 'tax_collection_model',
            'product sales' => 'product_sales',
            'product sales tax' => 'product_sales_tax',
            'shipping credits' => 'shipping_credits',
            'shipping credits tax' => 'shipping_credits_tax',
            'gift wrap credits' => 'gift_wrap_credits',
            'giftwrap credits tax' => 'gift_wrap_credits_tax',
            'Regulatory fee' => 'regulatory_fee',
            'Tax on regulatory fee' => 'tax_on_regulatory_fee',
            'promotional rebates' => 'promotional_rebates',
            'promotional rebates tax' => 'promotional_rebates_tax',
            //            '' => 'sales_tax_collected',//CA地区报告没有该表头数据
            //            '' => 'marketplace_facilitator_tax',//CA地区报告没有该表头数据
            'marketplace withheld tax' => 'marketplace_withheld_tax',
            'selling fees' => 'selling_fees',
            'fba fees' => 'fba_fees',
            'other transaction fees' => 'other_transaction_fees',
            'other' => 'other',
            'total' => 'total',
        ],
        Marketplace::MX()->countryCode() => [
            'fecha/hora' => 'date',
            'Id. de liquidación' => 'settlement_id',
            'tipo' => 'type',
            'Id. del pedido' => 'order_id',
            'sku' => 'sku',
            'descripción' => 'description',
            'cantidad' => 'quantity',
            'marketplace' => 'marketplace',
            //            '' => 'account_type',//MX地区报告没有该表头数据
            'cumplimiento' => 'fulfillment',
            'ciudad del pedido' => 'order_city',
            'estado del pedido' => 'order_state',
            'código postal del pedido' => 'order_postal',
            'modelo de recaudación de impuestos' => 'tax_collection_model',
            'ventas de productos' => 'product_sales',
            'impuesto de ventas de productos' => 'product_sales_tax',
            'créditos de envío' => 'shipping_credits',
            'impuesto de abono de envío' => 'shipping_credits_tax',
            'créditos por envoltorio de regalo' => 'gift_wrap_credits',
            'impuesto de créditos de envoltura' => 'gift_wrap_credits_tax',
            'Tarifa reglamentaria' => 'regulatory_fee',
            'Impuesto sobre tarifa reglamentaria' => 'tax_on_regulatory_fee',
            'descuentos promocionales' => 'promotional_rebates',
            'impuesto de reembolsos promocionales' => 'promotional_rebates_tax',
            //            '' => 'sales_tax_collected',//MX地区报告没有该表头数据
            //            '' => 'marketplace_facilitator_tax',//MX地区报告没有该表头数据
            'impuesto de retenciones en la plataforma' => 'marketplace_withheld_tax',
            'tarifas de venta' => 'selling_fees',
            'tarifas fba' => 'fba_fees',
            'tarifas de otra transacción' => 'other_transaction_fees',
            'otro' => 'other',
            'total' => 'total',
        ],
        Marketplace::DE()->countryCode() => [
            'Datum/Uhrzeit' => 'date',
            'Abrechnungsnummer' => 'settlement_id',
            'Typ' => 'type',
            'Bestellnummer' => 'order_id',
            'SKU' => 'sku',
            'Beschreibung' => 'description',
            'Menge' => 'quantity',
            'Marketplace' => 'marketplace',
            //            '' => 'account_type',//报告在当前地区不存在该字段
            'Versand' => 'fulfillment',
            'Ort der Bestellung' => 'order_city',
            'Bundesland' => 'order_state',
            'Postleitzahl' => 'order_postal',
            'Steuererhebungsmodell' => 'tax_collection_model',
            'Umsätze' => 'product_sales',
            'Produktumsatzsteuer' => 'product_sales_tax',
            'Gutschrift für Versandkosten' => 'shipping_credits',
            'Steuer auf Versandgutschrift' => 'shipping_credits_tax',
            'Gutschrift für Geschenkverpackung' => 'gift_wrap_credits',
            'Steuer auf Geschenkverpackungsgutschriften' => 'gift_wrap_credits_tax',
            //            '' => 'regulatory_fee',//报告在当前地区不存在该字段
            //            '' => 'tax_on_regulatory_fee',//报告在当前地区不存在该字段
            'Rabatte aus Werbeaktionen' => 'promotional_rebates',
            'Steuer auf Aktionsrabatte' => 'promotional_rebates_tax',
            //            '' => 'sales_tax_collected',//报告在当前地区不存在该字段
            //            '' => 'marketplace_facilitator_tax',//报告在当前地区不存在该字段
            'Einbehaltene Steuer auf Marketplace' => 'marketplace_withheld_tax',
            'Verkaufsgebühren' => 'selling_fees',
            'Gebühren zu Versand durch Amazon' => 'fba_fees',
            'Andere Transaktionsgebühren' => 'other_transaction_fees',
            'Andere' => 'other',
            'Gesamt' => 'total',
        ],
        Marketplace::FR()->countryCode() => [
            'date/heure' => 'date',
            'numéro de versement' => 'settlement_id',
            'type' => 'type',
            'numéro de la commande' => 'order_id',
            'sku' => 'sku',
            'description' => 'description',
            'quantité' => 'quantity',
            'Marketplace' => 'marketplace',
            //            '' => 'account_type',//报告在当前地区不存在该字段
            'traitement' => 'fulfillment',
            'ville d' => 'order_city',
            'Région d' => 'order_state',
            'code postal de la commande' => 'order_postal',
            'Modèle de perception des taxes' => 'tax_collection_model',
            'ventes de produits' => 'product_sales',
            'Taxes sur la vente des produits' => 'product_sales_tax',
            'crédits d' => 'shipping_credits',
            'taxe sur les crédits d’expédition' => 'shipping_credits_tax',
            'crédits sur l' => 'gift_wrap_credits',
            'Taxes sur les crédits cadeaux' => 'gift_wrap_credits_tax',
            //            '' => 'regulatory_fee',//报告在当前地区不存在该字段
            //            '' => 'tax_on_regulatory_fee',//报告在当前地区不存在该字段
            'Rabais promotionnels' => 'promotional_rebates',
            'Taxes sur les remises promotionnelles' => 'promotional_rebates_tax',
            //            '' => 'sales_tax_collected',//报告在当前地区不存在该字段
            //            '' => 'marketplace_facilitator_tax',//报告在当前地区不存在该字段
            'Taxes retenues sur le site de vente' => 'marketplace_withheld_tax',
            'frais de vente' => 'selling_fees',
            'Frais Expédié par Amazon' => 'fba_fees',
            'autres frais de transaction' => 'other_transaction_fees',
            'autre' => 'other',
            'total' => 'total',
        ],
        Marketplace::GB()->countryCode() => [
            'date/time' => 'date',
            'settlement id' => 'settlement_id',
            'type' => 'type',
            'order id' => 'order_id',
            'sku' => 'sku',
            'description' => 'description',
            'quantity' => 'quantity',
            'marketplace' => 'marketplace',
            //            '' => 'account_type',
            'fulfilment' => 'fulfillment',
            'order city' => 'order_city',
            'order state' => 'order_state',
            'order postal' => 'order_postal',
            'tax collection model' => 'tax_collection_model',
            'product sales' => 'product_sales',
            'product sales tax' => 'product_sales_tax',
            'shipping credits' => 'shipping_credits',
            'shipping credits tax' => 'shipping_credits_tax',
            'gift wrap credits' => 'gift_wrap_credits',
            'gift wrap credits tax' => 'gift_wrap_credits_tax',
            //            '' => 'regulatory_fee',
            //            '' => 'tax_on_regulatory_fee',
            'promotional rebates' => 'promotional_rebates',
            'promotional rebates tax' => 'promotional_rebates_tax',
            //            '' => 'sales_tax_collected',
            //            '' => 'marketplace_facilitator_tax',
            'marketplace withheld tax' => 'marketplace_withheld_tax',
            'selling fees' => 'selling_fees',
            'fba fees' => 'fba_fees',
            'other transaction fees' => 'other_transaction_fees',
            'other' => 'other',
            'total' => 'total',
        ],
        Marketplace::SE()->countryCode() => [
            'datum/tid' => 'date',
            'reglerings-id' => 'settlement_id',
            'typ' => 'type',
            'beställnings-id' => 'order_id',
            'sku' => 'sku',
            'beskrivning' => 'description',
            'antal' => 'quantity',
            'marknadsplats' => 'marketplace',
            //            '' => 'account_type',
            'leverans' => 'fulfillment',
            'stad för beställning' => 'order_city',
            'delstat för beställning' => 'order_state',
            'postadress för beställning' => 'order_postal',
            //            '' => 'tax_collection_model',
            'försäljning av produkter' => 'product_sales',
            //            '' => 'product_sales_tax',
            'fraktkrediter' => 'shipping_credits',
            //            '' => 'shipping_credits_tax',
            'krediter för presentinslagning' => 'gift_wrap_credits',
            //            '' => 'gift_wrap_credits_tax',
            //            '' => 'regulatory_fee',
            //            '' => 'tax_on_regulatory_fee',
            'kampanjrabatter' => 'promotional_rebates',
            //            '' => 'promotional_rebates_tax',
            'Inkasserad moms' => 'sales_tax_collected',
            'Skatt för marknadsplatsförmedlare' => 'marketplace_facilitator_tax',
            //            '' => 'marketplace_withheld_tax',
            'försäljningsavgifter' => 'selling_fees',
            'fba-avgifter' => 'fba_fees',
            'övriga transaktionsavgifter' => 'other_transaction_fees',
            'Övrigt' => 'other',
            'totalt' => 'total',
        ],
        Marketplace::IT()->countryCode() => [
            'Data/Ora:' => 'date',
            'Numero pagamento' => 'settlement_id',
            'Tipo' => 'type',
            'Numero ordine' => 'order_id',
            'SKU' => 'sku',
            'Descrizione' => 'description',
            'Quantità' => 'quantity',
            'Marketplace' => 'marketplace',
            //            '' => 'account_type',
            'Gestione' => 'fulfillment',
            'Città di provenienza dell' => 'order_city',
            'Provincia di provenienza dell' => 'order_state',
            'CAP dell' => 'order_postal',
            'modello di riscossione delle imposte' => 'tax_collection_model',
            'Vendite' => 'product_sales',
            'imposta sulle vendite dei prodotti' => 'product_sales_tax',
            'Accrediti per le spedizioni' => 'shipping_credits',
            'imposta accrediti per le spedizioni' => 'shipping_credits_tax',
            'Accrediti per confezioni regalo' => 'gift_wrap_credits',
            'imposta sui crediti confezione regalo' => 'gift_wrap_credits_tax',
            //            '' => 'regulatory_fee',
            //            '' => 'tax_on_regulatory_fee',
            'Sconti promozionali' => 'promotional_rebates',
            'imposta sugli sconti promozionali' => 'promotional_rebates_tax',
            //            '' => 'sales_tax_collected',
            //            '' => 'marketplace_facilitator_tax',
            'trattenuta IVA del marketplace' => 'marketplace_withheld_tax',
            'Commissioni di vendita' => 'selling_fees',
            'Costi del servizio Logistica di Amazon' => 'fba_fees',
            'Altri costi relativi alle transazioni' => 'other_transaction_fees',
            'Altro' => 'other',
            'totale' => 'total',
        ],
        Marketplace::ES()->countryCode() => [
            'fecha y hora' => 'date',
            'identificador de pago' => 'settlement_id',
            'tipo' => 'type',
            'número de pedido' => 'order_id',
            'sku' => 'sku',
            'descripción' => 'description',
            'cantidad' => 'quantity',
            'web de Amazon' => 'marketplace',
            //            '' => 'account_type',
            'gestión logística' => 'fulfillment',
            'ciudad de procedencia del pedido' => 'order_city',
            'comunidad autónoma de procedencia del pedido' => 'order_state',
            'código postal de procedencia del pedido' => 'order_postal',
            'Formulario de recaudación de impuestos' => 'tax_collection_model',
            'ventas de productos' => 'product_sales',
            'impuesto de ventas de productos' => 'product_sales_tax',
            'abonos de envío' => 'shipping_credits',
            'impuestos por abonos de envío' => 'shipping_credits_tax',
            'abonos de envoltorio para regalo' => 'gift_wrap_credits',
            'giftwrap credits tax' => 'gift_wrap_credits_tax',
            //            '' => 'regulatory_fee',
            //            '' => 'tax_on_regulatory_fee',
            'devoluciones promocionales' => 'promotional_rebates',
            'promotional rebates tax' => 'promotional_rebates_tax',
            //            '' => 'sales_tax_collected',
            //            '' => 'marketplace_facilitator_tax',
            'impuesto retenido en el sitio web' => 'marketplace_withheld_tax',
            'tarifas de venta' => 'selling_fees',
            'tarifas de Logística de Amazon' => 'fba_fees',
            'tarifas de otras transacciones' => 'other_transaction_fees',
            'otro' => 'other',
            'total' => 'total',
        ],
    ],

    'GET_LEDGER_SUMMARY_VIEW_DATA' => [],
    'GET_LEDGER_DETAIL_VIEW_DATA' => [],
    'GET_VENDOR_REAL_TIME_INVENTORY_REPORT' => [],
];
