<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Util\Amazon\Creator;

class GetShipmentItemsCreator implements CreatorInterface
{
    public string $query_type;

    public string $marketplace_id = '';

    public ?string $last_updated_after;

    public ?string $last_updated_before;

    public function getQueryType(): string
    {
        return $this->query_type;
    }

    public function setQueryType(string $query_type): void
    {
        $this->query_type = $query_type;
    }

    public function getMarketplaceId(): string
    {
        return $this->marketplace_id;
    }

    public function setMarketplaceId(string $marketplace_id): void
    {
        $this->marketplace_id = $marketplace_id;
    }

    public function getLastUpdatedAfter(): ?string
    {
        return $this->last_updated_after;
    }

    public function setLastUpdatedAfter(?string $last_updated_after): void
    {
        $this->last_updated_after = $last_updated_after;
    }

    public function getLastUpdatedBefore(): ?string
    {
        return $this->last_updated_before;
    }

    public function setLastUpdatedBefore(?string $last_updated_before): void
    {
        $this->last_updated_before = $last_updated_before;
    }
}
