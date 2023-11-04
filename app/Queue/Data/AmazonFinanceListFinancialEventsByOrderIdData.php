<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Queue\Data;

class AmazonFinanceListFinancialEventsByOrderIdData extends QueueData
{
    private int $merchant_id;

    private int $merchant_store_id;

    private string $order_id;

    public function getMerchantId(): int
    {
        return $this->merchant_id;
    }

    public function setMerchantId(int $merchant_id): void
    {
        $this->merchant_id = $merchant_id;
    }

    public function getMerchantStoreId(): int
    {
        return $this->merchant_store_id;
    }

    public function setMerchantStoreId(int $merchant_store_id): void
    {
        $this->merchant_store_id = $merchant_store_id;
    }

    public function getOrderId(): string
    {
        return $this->order_id;
    }

    public function setOrderId(string $order_id): void
    {
        $this->order_id = $order_id;
    }

    public function toJson(): string
    {
        return json_encode([
            'merchant_id' => $this->merchant_id,
            'merchant_store_id' => $this->merchant_store_id,
            'order_id' => $this->order_id,
        ], JSON_THROW_ON_ERROR);
    }

    public function parse(array $arr): self
    {
        $this->setMerchantId($arr['merchant_id']);
        $this->setMerchantStoreId($arr['merchant_store_id']);
        $this->setOrderId($arr['order_id']);
        return $this;
    }
}
