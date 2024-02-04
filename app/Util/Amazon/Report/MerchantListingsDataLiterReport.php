<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Util\Amazon\Report;

use App\Util\Amazon\Report\Runner\ReportRunnerInterface;

class MerchantListingsDataLiterReport extends ReportBase
{
    /**
     * @param ReportRunnerInterface $reportRunner
     * @return bool
     */
    public function run(ReportRunnerInterface $reportRunner): bool
    {
        // TODO: Implement run() method.
        return true;
    }

    /**
     * 请求报告
     * @param array $marketplace_ids
     * @param callable $func
     * @throws \Exception
     * @return void
     */
    public function requestReport(array $marketplace_ids, callable $func): void
    {
        foreach ($marketplace_ids as $marketplace_id) {
            is_callable($func) && $func($this, $this->getReportType(), $this->buildReportBody($this->getReportType(), [$marketplace_id]), [$marketplace_id]);
        }
    }
}
