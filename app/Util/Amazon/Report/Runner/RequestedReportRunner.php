<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Util\Amazon\Report\Runner;

class RequestedReportRunner implements ReportRunnerInterface
{
    private int $merchant_id;

    private int $merchant_store_id;

    private array $marketplace_ids;

    private string $report_type;

    private string $report_id;

    private string $region;

    private string $report_file_path;

    private ?string $data_start_time;

    private ?string $data_end_time;

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

    public function getMarketplaceIds(): array
    {
        return $this->marketplace_ids;
    }

    public function setMarketplaceIds(array $marketplace_ids): void
    {
        $this->marketplace_ids = $marketplace_ids;
    }

    public function getReportType(): string
    {
        return $this->report_type;
    }

    public function setReportType(string $report_type): void
    {
        $this->report_type = $report_type;
    }

    public function getReportId(): string
    {
        return $this->report_id;
    }

    public function setReportId(string $report_id): void
    {
        $this->report_id = $report_id;
    }

    public function getRegion(): string
    {
        return $this->region;
    }

    public function setRegion(string $region): void
    {
        $this->region = $region;
    }

    public function getReportFilePath(): string
    {
        return $this->report_file_path;
    }

    public function setReportFilePath(string $report_file_path): void
    {
        $this->report_file_path = $report_file_path;
    }

    public function getDataStartTime(): ?string
    {
        return $this->data_start_time;
    }

    public function setDataStartTime(?string $data_start_time): void
    {
        $this->data_start_time = $data_start_time;
    }

    public function getDataEndTime(): ?string
    {
        return $this->data_end_time;
    }

    public function setDataEndTime(?string $data_end_time): void
    {
        $this->data_end_time = $data_end_time;
    }
}
