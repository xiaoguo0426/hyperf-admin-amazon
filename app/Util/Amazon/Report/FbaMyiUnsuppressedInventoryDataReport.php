<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Util\Amazon\Report;

use App\Model\AmazonReportFbaMyiUnsuppressedInventoryDataModel;
use App\Util\Amazon\Report\Runner\ReportRunnerInterface;
use App\Util\Amazon\Report\Runner\RequestedReportRunner;

class FbaMyiUnsuppressedInventoryDataReport extends ReportBase
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
        $header_line = str_replace("\r\n", '', fgets($handle)); // 表头 需要处理换行符
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
            $merchant_id = $item['merchant_id'];
            $merchant_store_id = $item['merchant_store_id'];
            $sku = $item['sku'];
            $asin = $item['asin'];

            $collection = AmazonReportFbaMyiUnsuppressedInventoryDataModel::query()->where('merchant_id', $merchant_id)
                ->where('merchant_store_id', $merchant_store_id)
                ->where('sku', $sku)
                ->where('asin', $asin)
                ->first();

            if (is_null($collection)) {
                $collection = new AmazonReportFbaMyiUnsuppressedInventoryDataModel();
            }

            $collection->merchant_id = $item['merchant_id'];
            $collection->merchant_store_id = $item['merchant_store_id'];
            $collection->sku = $item['sku'];
            $collection->fnsku = $item['fnsku'];
            $collection->asin = $item['asin'];
            $collection->product_name = $item['product_name'];
            $collection->condition = $item['condition'];
            $collection->your_price = $item['your_price'];
            $collection->mfn_listing_exists = $item['mfn_listing_exists'];
            $collection->mfn_fulfillable_quantity = $item['mfn_fulfillable_quantity'];
            $collection->afn_listing_exists = $item['afn_listing_exists'];
            $collection->afn_warehouse_quantity = $item['afn_warehouse_quantity'];
            $collection->afn_fulfillable_quantity = $item['afn_fulfillable_quantity'];
            $collection->afn_unsellable_quantity = $item['afn_unsellable_quantity'];
            $collection->afn_reserved_quantity = $item['afn_reserved_quantity'];
            $collection->afn_total_quantity = $item['afn_total_quantity'];
            $collection->per_unit_volume = $item['per_unit_volume'];
            $collection->afn_inbound_working_quantity = $item['afn_inbound_working_quantity'];
            $collection->afn_inbound_shipped_quantity = $item['afn_inbound_shipped_quantity'];
            $collection->afn_inbound_receiving_quantity = $item['afn_inbound_receiving_quantity'];
            $collection->afn_researching_quantity = $item['afn_researching_quantity'];
            $collection->afn_reserved_future_supply = $item['afn_reserved_future_supply'];
            $collection->afn_future_supply_buyable = $item['afn_future_supply_buyable'];

            $collection->save();
        }
        return true;
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
