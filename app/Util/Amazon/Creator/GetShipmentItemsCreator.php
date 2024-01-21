<?php

namespace App\Util\Amazon\Creator;

class GetShipmentItemsCreator implements CreatorInterface
{
    /**
     * @var string
     */
    public string $query_type;

    /**
     * @var string
     */
    public string $marketplace_id = '';
    /**
     * @var string|null
     */
    public ?string $last_updated_after;

    /**
     * @var string|null
     */
    public ?string $last_updated_before;

    /**
     * @return string
     */
    public function getQueryType(): string
    {
        return $this->query_type;
    }

    /**
     * @param string $query_type
     */
    public function setQueryType(string $query_type): void
    {
        $this->query_type = $query_type;
    }

    /**
     * @return string
     */
    public function getMarketplaceId(): string
    {
        return $this->marketplace_id;
    }

    /**
     * @param string $marketplace_id
     */
    public function setMarketplaceId(string $marketplace_id): void
    {
        $this->marketplace_id = $marketplace_id;
    }

    /**
     * @return string|null
     */
    public function getLastUpdatedAfter(): ?string
    {
        return $this->last_updated_after;
    }

    /**
     * @param string|null $last_updated_after
     */
    public function setLastUpdatedAfter(?string $last_updated_after): void
    {
        $this->last_updated_after = $last_updated_after;
    }

    /**
     * @return string|null
     */
    public function getLastUpdatedBefore(): ?string
    {
        return $this->last_updated_before;
    }

    /**
     * @param string|null $last_updated_before
     */
    public function setLastUpdatedBefore(?string $last_updated_before): void
    {
        $this->last_updated_before = $last_updated_before;
    }

}