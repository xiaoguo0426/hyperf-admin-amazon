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
use Hyperf\Database\Model\ModelNotFoundException;

class FbaReimbursementsData extends ReportBase
{
    private array $date_list;

    public function __construct(int $merchant_id, int $merchant_store_id, string $region, string $report_type)
    {
        parent::__construct($merchant_id, $merchant_store_id, $region, $report_type);

        $last_7days_start_time = Carbon::now('UTC')->subDays(15)->format('Y-m-d 00:00:00'); // 最近7天
        $last_end_time = Carbon::yesterday('UTC')->format('Y-m-d 23:59:59');

        $this->date_list = [
            [
                'start_time' => $last_7days_start_time,
                'end_time' => $last_end_time,
            ],
        ];
    }

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

        foreach ($data as $item) {
            $collection = AmazonReportFbaReimbursementsDataModel::query()
                ->where('merchant_id', $merchant_id)
                ->where('merchant_store_id', $merchant_store_id)
                ->where('reimbursement_id', $item['reimbursement_id'])
                ->first();

            if (is_null($collection)) {
                $collection = new AmazonReportFbaReimbursementsDataModel();
            }

            try {
                $collection = AmazonReportFbaReimbursementsDataModel::query()->where('merchant_id', $merchant_id)
                    ->where('merchant_store_id', $merchant_store_id)
                    ->where('reimbursement_id', $item['reimbursement_id'])
                    ->firstOrFail();
            } catch (ModelNotFoundException $modelNotFoundException) {
                $collection->merchant_id = $merchant_id;
                $collection->merchant_store_id = $merchant_store_id;
                $collection->approval_date = isset($item['approval_date']) ? Carbon::parse($item['approval_date'])->format('Y-m-d H:i:s') : null;
                $collection->reimbursement_id = $item['reimbursement_id'];
                $collection->case_id = $item['case_id'] ?? '';
                $collection->amazon_order_id = $item['amazon_order_id'] ?? '';
                $collection->reason = $item['reason'] ?? '';
                $collection->sku = $item['sku'];
                $collection->fnsku = $item['fnsku'] ?? '';
                $collection->asin = $item['asin'] ?? '';
                $collection->product_name = $item['product_name'] ?? '';
            }

            $collection->condition = $item['condition'] ?? '';
            $collection->currency_unit = $item['currency_unit'] ?? '';
            $collection->amount_per_unit = $item['amount_per_unit'] ?? 0;
            $collection->amount_total = $item['amount_total'] ?? 0;
            $collection->quantity_reimbursed_cash = $item['quantity_reimbursed_cash'] ?? 0;
            $collection->quantity_reimbursed_inventory = $item['quantity_reimbursed_inventory'] ?? 0;
            $collection->quantity_reimbursed_total = $item['quantity_reimbursed_total'] ?? 0;
            $collection->original_reimbursement_id = $item['original_reimbursement_id'] ?? 0;
            $collection->original_reimbursement_type = $item['original_reimbursement_type'] ?? 0;

            $collection->save();
        }

        return true;
    }

    /**
     * 请求报告.
     *
     * @throws \Exception
     */
    public function requestReport(array $marketplace_ids, callable $func): void
    {
        foreach ($this->date_list as $item) {
            $this->setReportStartDate($item['start_time']);
            $this->setReportEndDate($item['end_time']);

            foreach ($marketplace_ids as $marketplace_id) {
                is_callable($func) && $func($this, $this->getReportType(), $this->buildReportBody($this->getReportType(), [$marketplace_id]), [$marketplace_id]);
            }
        }
    }

    /**
     * 处理报告.
     *
     * @throws \Exception
     *
     * @deprecated
     */
    public function processReport(callable $func, array $marketplace_ids): void
    {
        foreach ($this->date_list as $item) {
            $this->setReportStartDate($item['start_time']);
            $this->setReportEndDate($item['end_time']);

            foreach ($marketplace_ids as $marketplace_id) {
                is_callable($func) && $func($this, [$marketplace_id]);
            }
        }
    }

    public function checkReportDate(): bool
    {
        return true;
    }
}
