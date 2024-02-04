<?php

namespace App\Util\Amazon\Report\Runner;

class RequestedReportRunner implements ReportRunnerInterface
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
    private string $report_id;

    /**
     * @var string
     */
    private string $region;

    /**
     * @var string
     */
    private string $report_file_path;

    /**
     * @var string|null
     */
    private ?string $data_start_time;

    /**
     * @var string|null
     */
    private ?string $data_end_time;

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
    public function getReportId(): string
    {
        return $this->report_id;
    }

    /**
     * @param string $report_id
     */
    public function setReportId(string $report_id): void
    {
        $this->report_id = $report_id;
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

    /**
     * @return string|null
     */
    public function getDataStartTime(): ?string
    {
        return $this->data_start_time;
    }

    /**
     * @param string|null $data_start_time
     */
    public function setDataStartTime(?string $data_start_time): void
    {
        $this->data_start_time = $data_start_time;
    }

    /**
     * @return string|null
     */
    public function getDataEndTime(): ?string
    {
        return $this->data_end_time;
    }

    /**
     * @param string|null $data_end_time
     */
    public function setDataEndTime(?string $data_end_time): void
    {
        $this->data_end_time = $data_end_time;
    }
}