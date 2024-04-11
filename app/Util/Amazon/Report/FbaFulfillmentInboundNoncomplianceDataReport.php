<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Util\Amazon\Report;

use App\Model\AmazonReportFbaReimbursementsDataModel;
use App\Util\Amazon\Report\Runner\ReportRunnerInterface;
use App\Util\Amazon\Report\Runner\RequestedReportRunner;
use Carbon\Carbon;

class FbaFulfillmentInboundNoncomplianceDataReport extends ReportBase
{
    /**
     * @param RequestedReportRunner $reportRunner
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
        var_dump($data);
//        foreach ($data as $item) {
//        }

        return true;
    }

    //    /**
    //     * 请求报告.
    //     * @throws \Exception
    //     */
    //    public function requestReport(array $marketplace_ids, callable $func): void
    //    {
    //        foreach ($marketplace_ids as $marketplace_id) {
    //            is_callable($func) && $func($this, $this->report_type, $this->buildReportBody($this->report_type, [$marketplace_id]), [$marketplace_id]);
    //        }
    //    }
    //
    //    public function getReportFileName(array $marketplace_ids): string
    //    {
    //        return $this->report_type . '-' . $marketplace_ids[0];
    //    }
}
