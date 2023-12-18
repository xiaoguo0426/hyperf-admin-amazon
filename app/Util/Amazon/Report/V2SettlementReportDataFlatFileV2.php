<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Util\Amazon\Report;

use App\Model\AmazonSettlementReportDataFlatFileV2Model;
use App\Util\ConsoleLog;
use App\Util\RuntimeCalculator;
use Carbon\Carbon;
use Hyperf\Collection\Collection;
use Hyperf\Context\ApplicationContext;

class V2SettlementReportDataFlatFileV2 extends ReportBase
{
    public function run(string $report_id, string $file): bool
    {
        $currency_list = [
            'USD',
            'CAD',
            'MXN',
        ];
        $config = $this->getHeaderMap();

        $merchant_id = $this->getMerchantId();
        $merchant_store_id = $this->getMerchantStoreId();

        //        $logger = ApplicationContext::getContainer()->get(AmazonReportDocumentLog::class);
        $console = ApplicationContext::getContainer()->get(ConsoleLog::class);

        $splFileObject = new \SplFileObject($file, 'r');
        $header_line = str_replace("\r\n", '', $splFileObject->fgets());
        $headers = explode("\t", trim($header_line));

        $map = [];
        foreach ($headers as $index => $header) {
            if (! isset($config[$header])) {
                continue;
            }
            $map[$index] = $config[$header];
        }

        $summary_line = str_replace("\r\n", '', $splFileObject->fgets()); // 统计行数据不入库
        $summaries = explode("\t", trim($summary_line));

        $report_settlement_start_date = $summaries[1];
        $report_settlement_end_date = $summaries[2];
        $report_deposit_date = $summaries[3];
        //        $report_total_amount = $summaries[4];//整个报告的total_amount； 要慎用
        $report_currency = $summaries[5];

        $cur_date = Carbon::now()->format('Y-m-d H:i:s');
        $collection = new Collection();

        $splFileObject->seek(1); // 从第2行开始读取数据
        while (! $splFileObject->eof()) {
            $fgets = str_replace("\r\n", '', $splFileObject->fgets());
            if ($fgets === '') {
                continue;
            }
            $item = [];

            $row = explode("\t", $fgets);
            foreach ($map as $index => $value) {
                $val = trim($row[$index] ?? '');
                $item[$value] = $val;
            }

            if ($item['currency'] === '') {
                $item['currency'] = $report_currency;
            }

            $item['settlement_start_date'] = $this->formatDate($item['currency'], $item['settlement_start_date'] === '' ? $report_settlement_start_date : $item['settlement_start_date']);
            $item['settlement_end_date'] = $this->formatDate($item['currency'], $item['settlement_end_date'] === '' ? $report_settlement_end_date : $item['settlement_end_date']);
            $item['deposit_date'] = $this->formatDate($item['currency'], $item['deposit_date'] === '' ? $report_deposit_date : $item['deposit_date']);
            $item['posted_date_time'] = $this->formatDate($item['currency'], $item['posted_date_time'] === '' ? $report_deposit_date : $item['posted_date_time']);

            $item['merchant_id'] = $merchant_id;
            $item['merchant_store_id'] = $merchant_store_id;
            $item['created_at'] = $cur_date;
            $item['updated_at'] = $cur_date;
            $item['report_id'] = $report_id;
            $collection->push($item);
        }

        $console->notice(sprintf('报告ID:%s 开始处理数据. 数据长度:%s', $report_id, $collection->count()));
        $runtimeCalculator = new RuntimeCalculator();
        $runtimeCalculator->start();

        $collection->chunk(1000)->each(static function (Collection $list) use ($console): void {
            $console->info(sprintf('开始处理分页数据. 当前分页长度:%s', $list->count()));
            $runtimeCalculator = new RuntimeCalculator();
            $runtimeCalculator->start();

            $final = []; // 写入的数据集合

            foreach ($list as $item) {
                //                $merchant_id = $item['merchant_id'];
                //                $merchant_store_id = $item['merchant_store_id'];
                //                $settlement_id = $item['settlement_id'];
                //                $order_id = $item['order_id'];
                //                $transaction_type = $item['transaction_type'];
                //                $amount_type = $item['amount_type'];
                //                $amount_description = $item['amount_description'];
                //                $sku = $item['sku'];
                //                $report_id = $item['report_id'];
                //
                //                $model = AmazonSettlementReportDataFlatFileV2Model::query()
                //                    ->where('merchant_id', $merchant_id)
                //                    ->where('merchant_store_id', $merchant_store_id)
                //                    ->where('settlement_id', $settlement_id)
                //                    ->where('order_id', $order_id)
                //                    ->where('transaction_type', $transaction_type)
                //                    ->where('amount_type', $amount_type)
                //                    ->where('amount_description', $amount_description);
                //
                //                if ($transaction_type === 'Order' && ($amount_type === 'ItemFees' || $amount_type === 'ItemPrice' || $amount_type === 'ItemWithheldTax' || $amount_type === 'Promotion')) {
                //                    $model->where('sku', $sku);
                //                } elseif ($transaction_type === 'CouponRedemptionFee' && $amount_type === 'CouponRedemptionFee') {
                //                    $posted_date_time = $item['posted_date_time'];
                //                    $model->where('posted_date_time', $posted_date_time);
                //                } else if (){
                //                    var_dump($item);
                //
                //                    $console->error(sprintf('付款报告 未知类型 %s. 请检查数据.已跳过处理 report_id:%s', $transaction_type, $report_id));
                //                    exit();
                //                    continue;
                //                }
                //
                //                $collection = $model->first();
                //                if (is_null($collection)) {
                //                    $final[] = $item;
                //                }

                $final[] = $item;
            }

            if (count($final) > 0) {
                AmazonSettlementReportDataFlatFileV2Model::insert($final);
            }

            $console->info(sprintf('结束处理分页数据. 耗时:%s', $runtimeCalculator->stop()));
        });

        $console->notice(sprintf('报告ID:%s 结束处理数据. 耗时:%s', $report_id, $runtimeCalculator->stop()));

        return true;
    }

    private function formatDate($currency, $val): string
    {
        if ($currency === 'USD') {
            $val = Carbon::createFromFormat('Y-m-d H:i:s T', $val)->format('Y-m-d H:i:s');
        } elseif ($currency === 'CAD') {
            $val = Carbon::createFromFormat('d.m.Y H:i:s T', $val)->format('Y-m-d H:i:s');
        } elseif ($currency === 'MXN') {
            $val = Carbon::createFromFormat('d.m.Y H:i:s T', $val)->format('Y-m-d H:i:s');
        } else {
            return $val;
        }
        return $val;
    }
}
