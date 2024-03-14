<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Util\Amazon\Report\Runner;

class ScheduledReportRunner implements ReportRunnerInterface
{
    private int $merchant_id;

    private int $merchant_store_id;

    private string $region;

    private array $marketplace_ids;

    private string $report_type;

    private string $report_document_id;

    private string $report_file_path;

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

    public function getRegion(): string
    {
        return $this->region;
    }

    public function setRegion(string $region): void
    {
        $this->region = $region;
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

    public function getReportDocumentId(): string
    {
        return $this->report_document_id;
    }

    public function setReportDocumentId(string $report_document_id): void
    {
        $this->report_document_id = $report_document_id;
    }

    public function getReportFilePath(): string
    {
        return $this->report_file_path;
    }

    public function setReportFilePath(string $report_file_path): void
    {
        $this->report_file_path = $report_file_path;
    }
}
