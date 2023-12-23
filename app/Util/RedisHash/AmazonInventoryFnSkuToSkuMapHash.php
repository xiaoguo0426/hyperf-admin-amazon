<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Util\RedisHash;

use App\Util\Prefix;

class AmazonInventoryFnSkuToSkuMapHash extends AbstractRedisHash
{
    public function __construct(int $merchant_id, int $merchant_store_id)
    {
        $this->name = Prefix::amazonInventoryFnSkuMap($merchant_id, $merchant_store_id);
        parent::__construct();
    }

    /**
     * @param string $fn_sku
     * @throws \JsonException
     * @throws \RedisException
     * @return string|null
     */
    public function getSellerSkuByFnSku(string $fn_sku): ?string
    {
        return $this->getAttr($this->getPrefix($fn_sku));
    }

    /**
     * @param string $fn_sku
     * @param string $seller_sku
     * @throws \JsonException
     * @throws \RedisException
     * @return bool
     */
    public function setSellerSkuByFnSku(string $fn_sku, string $seller_sku): bool
    {
        return $this->setAttr($this->getPrefix($fn_sku), $seller_sku);
    }

    private function getPrefix(string $fn_sku): string
    {
        return sprintf('fn_sku:%s', $fn_sku);
    }

}
