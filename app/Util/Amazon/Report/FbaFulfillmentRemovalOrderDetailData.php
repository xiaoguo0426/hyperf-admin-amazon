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
use App\Util\Amazon\Report\Runner\RequestedReportRunner;

class FbaFulfillmentRemovalOrderDetailData extends ReportBase
{
    /**
     * @throws \Exception
     */
    public function __construct(int $merchant_id, int $merchant_store_id, string $region, string $report_type)
    {
        parent::__construct($merchant_id, $merchant_store_id, $region, $report_type);

        $start_time = date('Y-m-d 00:00:00', strtotime('-3 month'));
        $end_time = date('Y-m-d 00:00:00');
        $this->setReportStartDate($start_time);
        $this->setReportEndDate($end_time);
    }

    /**
     * @param RequestedReportRunner $reportRunner
     * @return bool
     */
    public function run(ReportRunnerInterface $reportRunner): bool
    {
        $config = $this->getHeaderMap();

        $merchant_id = $this->getMerchantId();
        $merchant_store_id = $this->getMerchantStoreId();

        $file = $reportRunner->getReportFilePath();
        $report_id = $reportRunner->getReportId();

        $handle = fopen($file, 'rb');
        $header_line = str_replace("\r\n", '', fgets($handle));
        // 表头 需要处理换行符
        $headers = explode("\t", $header_line);

        $map = [];
        foreach ($headers as $index => $header) {
            if (! isset($config[$header])) {
                continue;
            }
            $map[$index] = $config[$header];
        }

        $data = [];
        while (! feof($handle)) {
            $row = explode("\t", str_replace("\r\n", '', fgets($handle)));
            $item = [];
            foreach ($map as $index => $value) {
                $item[$value] = $row[$index];
            }
            $item['merchant_id'] = $merchant_id;
            $item['merchant_store_id'] = $merchant_store_id;

            $data[] = $item;
        }
        fclose($handle);
        return true;
    }

    /**
     * @param string $report_type
     * @param array $marketplace_ids
     * @return CreateReportSpecification
     */
    public function buildReportBody(string $report_type, array $marketplace_ids): CreateReportSpecification
    {
        return new CreateReportSpecification([
            'report_options' => null,
            'report_type' => $report_type,//报告类型
            'data_start_time' => $this->getReportStartDate(),//报告数据开始时间
            'data_end_time' => $this->getReportEndDate(),//报告数据结束时间
            'marketplace_ids' => $marketplace_ids//市场标识符列表
        ]);
    }

    public function checkReportDate(): bool
    {
        return true;
    }
}
