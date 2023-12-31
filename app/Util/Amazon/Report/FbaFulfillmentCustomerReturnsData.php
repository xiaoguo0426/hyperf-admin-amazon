<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Util\Amazon\Report;

use App\Model\AmazonReportFbaFulfillmentCustomerReturnDataModel;
use Carbon\Carbon;
use Hyperf\Database\Model\ModelNotFoundException;

class FbaFulfillmentCustomerReturnsData extends ReportBase
{
    /**
     * @throws \Exception
     */
    public function __construct(string $report_type, int $merchant_id, int $merchant_store_id)
    {
        parent::__construct($report_type, $merchant_id, $merchant_store_id);

        $yesterday = Carbon::yesterday();
        $start_time = $yesterday->format('Y-m-d 00:00:00');
        $end_time = $yesterday->format('Y-m-d 23:59:59');
        $this->setReportStartDate($start_time);
        $this->setReportEndDate($end_time);
    }

    public function run(string $report_id, string $file): bool
    {
        $config = $this->getHeaderMap();

        $merchant_id = $this->getMerchantId();
        $merchant_store_id = $this->getMerchantStoreId();

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
            // 没有sku或则订单id则跳过
            if (trim($item['sku']) === '' || trim($item['order_id']) === '') {
                continue;
            }

            $return_date = Carbon::createFromFormat('Y-m-d\TH:i:sP', $item['return_date'])->format('Y-m-d H:i:s');

            $sku = $item['sku'];
            $fnsku = $item['fnsku'];
            $asin = $item['asin'];
            $order_id = $item['order_id'];
            $fulfillment_center_id = $item['fulfillment_center_id'];

            try {
                $collection = AmazonReportFbaFulfillmentCustomerReturnDataModel::query()->where('merchant_id', $merchant_id)
                    ->where('merchant_store_id', $merchant_store_id)
                    ->where('return_date', $return_date)
                    ->where('sku', $sku)
                    ->where('asin', $asin)
                    ->where('order_id', $order_id)
                    ->where('fulfillment_center_id', $fulfillment_center_id)
                    ->firstOrFail();
            } catch (ModelNotFoundException $modelNotFoundException) {
                $collection = new AmazonReportFbaFulfillmentCustomerReturnDataModel();
                $collection->merchant_id = $merchant_id;
                $collection->merchant_store_id = $merchant_store_id;
                $collection->return_date = $return_date;
                $collection->order_id = $order_id;
                $collection->sku = $sku;
                $collection->asin = $asin;
                $collection->fnsku = $fnsku;
                $collection->product_name = preg_replace('/[^a-zA-Z0-9 ]/i', '', $item['product_name']);
                $collection->fulfillment_center_id = $item['fulfillment_center_id'];
            }
            $collection->quantity = $item['quantity'];
            $collection->detailed_disposition = $item['detailed_disposition'];
            $collection->reason = $item['reason'];
            $collection->status = $item['status'];
            $collection->license_plate_number = $item['license_plate_number'];
            $collection->customer_comments = htmlentities(str_replace('�', '\'', $item['customer_comments']));

            $collection->save();
        }

        return true;
    }

    public function checkReportDate(): bool
    {
        return true;
    }
}
