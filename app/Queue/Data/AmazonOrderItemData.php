<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Queue\Data;

class AmazonOrderItemData extends QueueData implements \JsonSerializable
{
    private int $merchant_id;

    private int $merchant_store_id;

    private string $region;

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

    public function getRegion(): string
    {
        return $this->region;
    }

    public function setRegion(string $region): void
    {
        $this->region = $region;
    }

    public function getOrderId(): array
    {
        return explode(',', $this->order_id);
    }

    public function setOrderId(array $order_ids): void
    {
        $this->order_id = implode(',', $order_ids);
    }

    public function toJson(): string
    {
        return json_encode([
            'merchant_id' => $this->merchant_id,
            'merchant_store_id' => $this->merchant_store_id,
            'region' => $this->region,
            'order_id' => $this->order_id,
        ], JSON_THROW_ON_ERROR);
    }

    public function parse(array $arr): void
    {
        $this->merchant_id = $arr['merchant_id'];
        $this->merchant_store_id = $arr['merchant_store_id'];
        $this->region = $arr['region'];
        $this->order_id = $arr['order_id'];
    }

    /**
     * @throws \JsonException
     */
    public static function fromJson(mixed $json): AmazonOrderItemData
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR); // 解码为关联数组
        return new self(
            $data['merchant_id'],
            $data['merchant_store_id'],
            $data['region'],
            $data['order_id']
        );
    }

    public function jsonSerialize(): mixed
    {
        return [
            'merchant_id' => $this->merchant_id,
            'merchant_store_id' => $this->merchant_store_id,
            'region' => $this->region,
            'order_id' => $this->order_id,
        ];
    }
}
