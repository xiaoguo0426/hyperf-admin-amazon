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

class MerchantListingsAllDataReport extends ReportBase
{
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
                'custom' => 'true',
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

}
