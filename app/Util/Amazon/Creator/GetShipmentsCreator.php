<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Util\Amazon\Creator;

class GetShipmentsCreator implements CreatorInterface
{
    public string $query_type;

    public string $marketplace_id = '';

    /**
     * @var string[]
     */
    public array $shipment_status_list = [];

    /**
     * @var null|string[]
     */
    public ?array $shipment_id_list;

    public ?string $last_updated_after;

    public ?string $last_updated_before;

    public function getQueryType(): string
    {
        return $this->query_type;
    }

    /**
     * @return $this
     */
    public function setQueryType(string $query_type): GetShipmentsCreator
    {
        $this->query_type = $query_type;
        return $this;
    }

    public function getMarketplaceId(): string
    {
        return $this->marketplace_id;
    }

    /**
     * @return $this
     */
    public function setMarketplaceId(string $marketplace_id): GetShipmentsCreator
    {
        $this->marketplace_id = $marketplace_id;
        return $this;
    }

    public function getShipmentStatusList(): array
    {
        return $this->shipment_status_list;
    }

    /**
     * @return $this
     */
    public function setShipmentStatusList(array $shipment_status_list): GetShipmentsCreator
    {
        $this->shipment_status_list = $shipment_status_list;
        return $this;
    }

    /**
     * @return null|string[]
     */
    public function getShipmentIdList(): ?array
    {
        return $this->shipment_id_list;
    }

    /**
     * @param null|string[] $shipment_id_list
     * @return $this
     */
    public function setShipmentIdList(?array $shipment_id_list): GetShipmentsCreator
    {
        $this->shipment_id_list = $shipment_id_list;
        return $this;
    }

    public function getLastUpdatedAfter(): ?string
    {
        return $this->last_updated_after;
    }

    /**
     * @return $this
     */
    public function setLastUpdatedAfter(?string $last_updated_after): GetShipmentsCreator
    {
        $this->last_updated_after = $last_updated_after;
        return $this;
    }

    public function getLastUpdatedBefore(): ?string
    {
        return $this->last_updated_before;
    }

    /**
     * @return $this
     */
    public function setLastUpdatedBefore(?string $last_updated_before): GetShipmentsCreator
    {
        $this->last_updated_before = $last_updated_before;
        return $this;
    }
}
