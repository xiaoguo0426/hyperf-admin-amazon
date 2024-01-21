<?php

namespace App\Util\Amazon\Creator;

class GetShipmentsCreator implements CreatorInterface
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
     * @var string[] $shipment_status_list
     */
    public array $shipment_status_list = [];

    /**
     * @var string[]|null
     */
    public ?array $shipment_id_list;

    public ?string $last_updated_after;

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
     * @return $this
     */
    public function setQueryType(string $query_type): GetShipmentsCreator
    {
        $this->query_type = $query_type;
        return $this;
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
     * @return $this
     */
    public function setMarketplaceId(string $marketplace_id): GetShipmentsCreator
    {
        $this->marketplace_id = $marketplace_id;
        return $this;
    }

    /**
     * @return array
     */
    public function getShipmentStatusList(): array
    {
        return $this->shipment_status_list;
    }

    /**
     * @param array $shipment_status_list
     * @return $this
     */
    public function setShipmentStatusList(array $shipment_status_list): GetShipmentsCreator
    {
        $this->shipment_status_list = $shipment_status_list;
        return $this;
    }

    /**
     * @return string[]|null
     */
    public function getShipmentIdList(): ?array
    {
        return $this->shipment_id_list;
    }

    /**
     * @param string[]|null $shipment_id_list
     * @return $this
     */
    public function setShipmentIdList(?array $shipment_id_list): GetShipmentsCreator
    {
        $this->shipment_id_list = $shipment_id_list;
        return $this;
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
     * @return $this
     */
    public function setLastUpdatedAfter(?string $last_updated_after): GetShipmentsCreator
    {
        $this->last_updated_after = $last_updated_after;
        return $this;
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
     * @return $this
     */
    public function setLastUpdatedBefore(?string $last_updated_before): GetShipmentsCreator
    {
        $this->last_updated_before = $last_updated_before;
        return $this;
    }
}