<?php

namespace App\Util\Amazon\Report\Runner;

class ScheduledReportRunner implements ReportRunnerInterface
{
    /**
     * @var int
     */
    private int $merchant_id;

    /**
     * @var int
     */
    private int $merchant_store_id;

    /**
     * @var string
     */
    private string $region;

    /**
     * @var array
     */
    private array $marketplace_ids;

    /**
     * @var string
     */
    private string $report_type;

    /**
     * @var string
     */
    private string $report_document_id;

    /**
     * @var string
     */
    private string $report_file_path;

    /**
     * @return int
     */
    public function getMerchantId(): int
    {
        return $this->merchant_id;
    }

    /**
     * @param int $merchant_id
     */
    public function setMerchantId(int $merchant_id): void
    {
        $this->merchant_id = $merchant_id;
    }

    /**
     * @return int
     */
    public function getMerchantStoreId(): int
    {
        return $this->merchant_store_id;
    }

    /**
     * @param int $merchant_store_id
     */
    public function setMerchantStoreId(int $merchant_store_id): void
    {
        $this->merchant_store_id = $merchant_store_id;
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
        return $this->marketplace_ids;
    }

    /**
     * @param array $marketplace_ids
     */
    public function setMarketplaceIds(array $marketplace_ids): void
    {
        $this->marketplace_ids = $marketplace_ids;
    }

    /**
     * @return string
     */
    public function getReportType(): string
    {
        return $this->report_type;
    }

    /**
     * @param string $report_type
     */
    public function setReportType(string $report_type): void
    {
        $this->report_type = $report_type;
    }

    /**
     * @return string
     */
    public function getReportDocumentId(): string
    {
        return $this->report_document_id;
    }

    /**
     * @param string $report_document_id
     */
    public function setReportDocumentId(string $report_document_id): void
    {
        $this->report_document_id = $report_document_id;
    }

    /**
     * @return string
     */
    public function getReportFilePath(): string
    {
        return $this->report_file_path;
    }

    /**
     * @param string $report_file_path
     */
    public function setReportFilePath(string $report_file_path): void
    {
        $this->report_file_path = $report_file_path;
    }
}