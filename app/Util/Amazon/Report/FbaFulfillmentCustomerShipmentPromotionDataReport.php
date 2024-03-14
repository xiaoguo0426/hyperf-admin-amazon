<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Util\Amazon\Report;

use AmazonPHP\SellingPartner\Exception\InvalidArgumentException;
use AmazonPHP\SellingPartner\Model\Reports\CreateReportSpecification;
use App\Util\Amazon\Report\Runner\ReportRunnerInterface;
use App\Util\Amazon\Report\Runner\RequestedReportRunner;

class FbaFulfillmentCustomerShipmentPromotionDataReport extends ReportBase
{
    /**
     * @param RequestedReportRunner $reportRunner
     */
    public function run(ReportRunnerInterface $reportRunner): bool
    {
        //        $logger = ApplicationContext::getContainer()->get(AmazonReportDocumentLog::class);
        //        $console = ApplicationContext::getContainer()->get(ConsoleLog::class);

        $file = $reportRunner->getReportFilePath();
        $report_id = $reportRunner->getReportId();

        $merchant_id = $this->getMerchantId();
        $merchant_store_id = $this->getMerchantStoreId();
        $region = $this->region;
        $config = $this->getHeaderMap();

        return true;
    }

    public function buildReportBody(string $report_type, array $marketplace_ids): CreateReportSpecification
    {
        return new CreateReportSpecification([
            'report_type' => $report_type, // 报告类型
            'data_start_time' => $this->getReportStartDate(), // 报告数据开始时间
            'data_end_time' => $this->getReportEndDate(), // 报告数据结束时间
            'marketplace_ids' => $marketplace_ids, // 市场标识符列表
        ]);
    }

    /**
     * 报告是否需要指定开始时间与结束时间.
     */
    public function reportDateRequired(): bool
    {
        return true;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function checkReportDate(): bool
    {
        if (is_null($this->report_start_date) || is_null($this->report_end_date)) {
            return false;
        }
        return true;
    }
}
