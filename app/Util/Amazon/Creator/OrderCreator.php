<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Util\Amazon\Creator;

use AmazonPHP\SellingPartner\Model\Orders\ItemApprovalType;

class OrderCreator implements CreatorInterface
{
    /**
     * @var array<string>
     */
    public array $marketplace_ids = [];

    public ?string $created_after = null;

    public ?string $created_before = null;

    public ?string $last_updated_after = null;

    public ?string $last_updated_before = null;

    /**
     * @var null|array<string>
     */
    public ?array $order_statuses = null;

    public ?string $fulfillment_channels = null;

    /**
     * @var null|array<string>
     */
    public ?array $payment_methods = null;

    public ?string $buyer_email = null;

    public ?string $seller_order_id = null;

    public int $max_results_per_page = 100;

    public ?array $easy_ship_shipment_statuses = null;

    /**
     * @var null|array<string>
     */
    public ?array $electronic_invoice_statuses = null;

    public ?string $next_token;

    /**
     * @var null|array<string>
     */
    public ?array $amazon_order_ids;

    /**
     * @var null|array<string>
     */
    public ?array $actual_fulfillment_supply_source_id = null;

    public ?bool $is_ispu = null;

    public ?string $store_chain_store_id = null;

    /**
     * @var null|array<ItemApprovalType>
     */
    public ?array $item_approval_types = null;

    /**
     * @var null|?ItemApprovalStatus[]
     */
    public ?array $item_approval_status = null;

    public function getMarketplaceIds(): array
    {
        return $this->marketplace_ids;
    }

    public function setMarketplaceIds(array $marketplace_ids): OrderCreator
    {
        $this->marketplace_ids = $marketplace_ids;
        return $this;
    }

    public function getCreatedAfter(): ?string
    {
        return $this->created_after;
    }

    public function setCreatedAfter(?string $created_after): OrderCreator
    {
        $this->created_after = $created_after;
        return $this;
    }

    public function getCreatedBefore(): ?string
    {
        return $this->created_before;
    }

    public function setCreatedBefore(?string $created_before): OrderCreator
    {
        $this->created_before = $created_before;
        return $this;
    }

    public function getLastUpdatedAfter(): ?string
    {
        return $this->last_updated_after;
    }

    public function setLastUpdatedAfter(?string $last_updated_after): OrderCreator
    {
        $this->last_updated_after = $last_updated_after;
        return $this;
    }

    public function getLastUpdatedBefore(): ?string
    {
        return $this->last_updated_before;
    }

    public function setLastUpdatedBefore(?string $last_updated_before): OrderCreator
    {
        $this->last_updated_before = $last_updated_before;
        return $this;
    }

    public function getOrderStatuses(): ?array
    {
        return $this->order_statuses;
    }

    public function setOrderStatuses(?array $order_statuses): OrderCreator
    {
        $this->order_statuses = $order_statuses;
        return $this;
    }

    public function getFulfillmentChannels(): ?string
    {
        return $this->fulfillment_channels;
    }

    public function setFulfillmentChannels(?string $fulfillment_channels): OrderCreator
    {
        $this->fulfillment_channels = $fulfillment_channels;
        return $this;
    }

    public function getPaymentMethods(): ?array
    {
        return $this->payment_methods;
    }

    public function setPaymentMethods(?array $payment_methods): OrderCreator
    {
        $this->payment_methods = $payment_methods;
        return $this;
    }

    public function getBuyerEmail(): ?string
    {
        return $this->buyer_email;
    }

    public function setBuyerEmail(?string $buyer_email): OrderCreator
    {
        $this->buyer_email = $buyer_email;
        return $this;
    }

    public function getSellerOrderId(): ?string
    {
        return $this->seller_order_id;
    }

    public function setSellerOrderId(?string $seller_order_id): OrderCreator
    {
        $this->seller_order_id = $seller_order_id;
        return $this;
    }

    public function getMaxResultsPerPage(): int
    {
        return $this->max_results_per_page;
    }

    public function setMaxResultsPerPage(int $max_results_per_page): OrderCreator
    {
        $this->max_results_per_page = $max_results_per_page;
        return $this;
    }

    public function getEasyShipShipmentStatuses(): ?array
    {
        return $this->easy_ship_shipment_statuses;
    }

    public function setEasyShipShipmentStatuses(?array $easy_ship_shipment_statuses): OrderCreator
    {
        $this->easy_ship_shipment_statuses = $easy_ship_shipment_statuses;
        return $this;
    }

    public function getElectronicInvoiceStatuses(): ?array
    {
        return $this->electronic_invoice_statuses;
    }

    public function setElectronicInvoiceStatuses(?array $electronic_invoice_statuses): OrderCreator
    {
        $this->electronic_invoice_statuses = $electronic_invoice_statuses;
        return $this;
    }

    public function getNextToken(): ?string
    {
        return $this->next_token;
    }

    public function setNextToken(?string $next_token): OrderCreator
    {
        $this->next_token = $next_token;
        return $this;
    }

    public function getAmazonOrderIds(): ?array
    {
        return $this->amazon_order_ids;
    }

    public function setAmazonOrderIds(?array $amazon_order_ids): OrderCreator
    {
        $this->amazon_order_ids = $amazon_order_ids;
        return $this;
    }

    public function getActualFulfillmentSupplySourceId(): ?array
    {
        return $this->actual_fulfillment_supply_source_id;
    }

    public function setActualFulfillmentSupplySourceId(?array $actual_fulfillment_supply_source_id): OrderCreator
    {
        $this->actual_fulfillment_supply_source_id = $actual_fulfillment_supply_source_id;
        return $this;
    }

    public function getIsIspu(): ?bool
    {
        return $this->is_ispu;
    }

    public function setIsIspu(?bool $is_ispu): OrderCreator
    {
        $this->is_ispu = $is_ispu;
        return $this;
    }

    public function getStoreChainStoreId(): ?string
    {
        return $this->store_chain_store_id;
    }

    public function setStoreChainStoreId(?string $store_chain_store_id): OrderCreator
    {
        $this->store_chain_store_id = $store_chain_store_id;
        return $this;
    }

    public function getItemApprovalTypes(): ?array
    {
        return $this->item_approval_types;
    }

    public function setItemApprovalTypes(?array $item_approval_types): OrderCreator
    {
        $this->item_approval_types = $item_approval_types;
        return $this;
    }

    public function getItemApprovalStatus(): ?array
    {
        return $this->item_approval_status;
    }

    public function setItemApprovalStatus(?array $item_approval_status): OrderCreator
    {
        $this->item_approval_status = $item_approval_status;
        return $this;
    }
}
