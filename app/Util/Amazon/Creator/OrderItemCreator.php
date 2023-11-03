<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Util\Amazon\Creator;

class OrderItemCreator implements CreatorInterface
{
    /**
     * @var string[]
     */
    public array $amazon_order_ids;

    public function setAmazonOrderIds(array $amazon_order_ids): void
    {
        $this->amazon_order_ids = $amazon_order_ids;
    }

    public function getAmazonOrderIds(): array
    {
        return $this->amazon_order_ids;
    }
}
