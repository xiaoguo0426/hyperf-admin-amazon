<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Util\Amazon\Report;

use App\Model\AmazonFbaFulfillmentLongTermStorageFeeChargesDataModel;
use App\Util\Amazon\Report\Runner\ReportRunnerInterface;
use App\Util\Amazon\Report\Runner\RequestedReportRunner;
use Carbon\Carbon;
use Hyperf\Collection\Collection;
use Hyperf\Database\Model\ModelNotFoundException;

class FbaFulfillmentLongTermStorageFeeChargesDataReport extends ReportBase
{
    public function __construct(int $merchant_id, int $merchant_store_id, string $region, string $report_type)
    {
        parent::__construct($merchant_id, $merchant_store_id, $region, $report_type);
        // 默认获取上个月的报告
        $data = Carbon::now('UTC')->subMonth();
        $start_time = $data->firstOfMonth()->format('Y-m-d 00:00:00');
        $end_time = $data->endOfMonth()->format('Y-m-d 23:59:59');

        $this->setReportStartDate($start_time);
        $this->setReportEndDate($end_time);
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
        $collections = new Collection();
        while (! feof($handle)) {
            $row = explode("\t", str_replace("\r\n", '', fgets($handle)));
            $item = [];
            foreach ($map as $index => $value) {
                $val = $row[$index];
                if ($value === 'snapshot_date') {
                    $val = Carbon::createFromFormat('Y-m-d\TH:i:s+P', $val)->format('Y-m-d H:i:s');
                }
                $item[$value] = $val;
            }
            $item['merchant_id'] = $merchant_id;
            $item['merchant_store_id'] = $merchant_store_id;
            $collections->push($item);
        }
        fclose($handle);

        $collections->each(function ($collection) {
            $merchant_id = $collection['merchant_id'];
            $merchant_store_id = $collection['merchant_store_id'];
            $snapshot_date = $collection['snapshot_date'];
            $sku = $collection['sku'];
            $asin = $collection['asin'];
            $country = $collection['country'];

            try {
                $model = AmazonFbaFulfillmentLongTermStorageFeeChargesDataModel::query()
                    ->where('merchant_id', $merchant_id)
                    ->where('merchant_store_id', $merchant_store_id)
                    ->where('snapshot_date', $snapshot_date)
                    ->where('sku', $sku)
                    ->where('asin', $asin)
                    ->where('country', $country)
                    ->firstOrFail();
            } catch (ModelNotFoundException $modelNotFoundException) {
                $model = new AmazonFbaFulfillmentLongTermStorageFeeChargesDataModel();
                $model->merchant_id = $merchant_id;
                $model->merchant_store_id = $merchant_store_id;
                $model->snapshot_date = $snapshot_date;
                $model->sku = $sku;
                $model->fnsku = $collection['fnsku'];
                $model->asin = $asin;
                $model->product_name = preg_replace('/[^a-zA-Z0-9 ]/i', '', $collection['product_name']);
            }

            $model->condition = $collection['condition'];
            $model->per_unit_volume = $collection['per_unit_volume'];
            $model->currency = $collection['currency'];
            $model->volume_unit = $collection['volume_unit'];
            $model->country = $collection['country'];
            $model->qty_charged = $collection['qty_charged'];
            $model->amount_charged = $collection['amount_charged'];
            $model->surcharge_age_tier = $collection['surcharge_age_tier'];
            $model->rate_surcharge = $collection['rate_surcharge'];

            $model->save();
        });

        return true;
    }

    public function checkReportDate(): bool
    {
        return true;
    }
}
