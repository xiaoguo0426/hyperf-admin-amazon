<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Queue\Data;

class AmazonReportDocumentActionData extends QueueData
{
    private int $merchant_id;

    private int $merchant_store_id;
    private string $region;

    private string $marketplace_ids;

    private string $report_document_id;

    private string $report_type;

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

    public function getReportDocumentId(): string
    {
        return $this->report_document_id;
    }

    /**
     * @return string
     */
    public function getRegion(): string
    {
        return $this->region;
    }

    /**
     * @param string $region
     */
    public function setRegion(string $region): void
    {
        $this->region = $region;
    }

    /**
     * @return array
     */
    public function getMarketplaceIds(): array
    {
        return explode(',', $this->marketplace_ids);
    }

    /**
     * @param array $marketplace_ids
     */
    public function setMarketplaceIds(array $marketplace_ids): void
    {
        $this->marketplace_ids = implode(',', $marketplace_ids);
    }

    public function setReportDocumentId(string $report_document_id): void
    {
        $this->report_document_id = $report_document_id;
    }

    public function getReportType(): string
    {
        return $this->report_type;
    }

    public function setReportType(string $report_type): void
    {
        $this->report_type = $report_type;
    }

    public function toJson(): string
    {
        return json_encode([
            'merchant_id' => $this->merchant_id,
            'merchant_store_id' => $this->merchant_store_id,
            'region' => $this->region,
            'marketplace_ids' => $this->marketplace_ids,
            'report_document_id' => $this->report_document_id,
            'report_type' => $this->report_type,
        ], JSON_THROW_ON_ERROR);
    }

    public function parse(array $arr): void
    {
        $this->setMerchantId($arr['merchant_id']);
        $this->setMerchantStoreId($arr['merchant_store_id']);
        $this->setRegion($arr['region']);
        $this->setMarketplaceIds(explode(',', $arr['marketplace_ids']));
        $this->setReportDocumentId($arr['report_document_id']);
        $this->setReportType($arr['report_type']);
    }
}
