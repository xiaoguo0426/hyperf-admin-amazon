<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Util\Amazon\Report;

use AmazonPHP\SellingPartner\Model\Reports\CreateReportSpecification;
use App\Util\Amazon\Report\Runner\ReportRunnerInterface;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class BrandAnalyticsMarketBasketReport extends ReportBase
{
    /**
     * @throws \Exception
     */
    public function __construct(int $merchant_id, int $merchant_store_id, string $region, string $report_type)
    {
        parent::__construct($merchant_id, $merchant_store_id, $region, $report_type);

        $start_time = Carbon::now()->startOfWeek(CarbonInterface::SUNDAY)->format('Y-m-d 00:00:00');
        $end_time = Carbon::now()->endOfWeek(CarbonInterface::SATURDAY)->format('Y-m-d 23:59:59');

        //        $start_time = Carbon::now()->startOfMonth()->format('Y-m-d 00:00:00');
        //        $end_time = Carbon::now()->endOfMonth()->format('Y-m-d 23:59:59');

        $this->setReportStartDate($start_time);
        $this->setReportEndDate($end_time);
    }

    public function run(ReportRunnerInterface $reportRunner): bool
    {
        // TODO: Implement run() method.
        return true;
    }

    /**
     * @throws \Exception
     */
    public function buildReportBody(string $report_type, array $marketplace_ids): CreateReportSpecification
    {
        return new CreateReportSpecification([
            'report_options' => [
                'reportPeriod' => 'DAY',
//                'reportPeriod' => 'WEEK',
                //                'reportPeriod' => 'MONTH',
            ],
            'report_type' => $report_type, // 报告类型
            'data_start_time' => $this->getReportStartDate(), // 报告数据开始时间
            'data_end_time' => $this->getReportEndDate(), // 报告数据结束时间
            'marketplace_ids' => $marketplace_ids, // 市场标识符列表
        ]);
    }

    /**
     * @throws \Exception
     */
    public function requestReport(array $marketplace_ids, callable $func): void
    {
        foreach ($marketplace_ids as $marketplace_id) {
            is_callable($func) && $func($this, $this->getReportType(), $this->buildReportBody($this->getReportType(), [$marketplace_id]), [$marketplace_id]);
        }
    }

    public function getReportFileName(array $marketplace_ids, string $region, string $report_id = ''): string
    {
        return $this->getReportType() . '-' . $marketplace_ids[0] . '-' . $this->getReportStartDate()?->format('Ymd') . '-' . $this->getReportEndDate()?->format('Ymd');
    }

    public function checkReportDate(): bool
    {
        return true;
    }
}
