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
use App\Util\Amazon\Report\Runner\ReportRunnerInterface;
use App\Util\Amazon\Report\Runner\ScheduledReportRunner;
use App\Util\ConsoleLog;
use App\Util\Log\AmazonReportDocumentLog;
use App\Util\RuntimeCalculator;
use Carbon\Carbon;
use Hyperf\Collection\Collection;
use Hyperf\Context\ApplicationContext;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class V2SettlementReportDataFlatFileV2 extends ReportBase
{
    /**
     * @param ScheduledReportRunner $reportRunner
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \JsonException
     */
    public function run(ReportRunnerInterface $reportRunner): bool
    {
        $config = $this->getHeaderMap();

        $merchant_id = $this->getMerchantId();
        $merchant_store_id = $this->getMerchantStoreId();

        $file = $reportRunner->getReportFilePath();
        $report_id = $reportRunner->getReportDocumentId();

        $logger = ApplicationContext::getContainer()->get(AmazonReportDocumentLog::class);
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

        // seek的bug在8.0.1中修复，且hyperf3.1框架要求>=8.1,所以此处仅作记录，其他php版本在在使用时注意甄别
        // 统计行不入库
        // https://bugs.php.net/bug.php?id=62004   https://github.com/php/php-src/pull/6434
        //        $currentVersion = phpversion();
        //        if (version_compare($currentVersion, '8.0.1', '>=')) {
        //            $splFileObject->seek(2); // 从第2行开始读取数据
        //        } else {
        //            $splFileObject->seek(1); // 从第2行开始读取数据
        //        }
        $splFileObject->seek(2); // 从第2行开始读取数据

        $md5_hash_idx_map = [];
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

            $item['settlement_start_date'] = $this->formatDate($item['currency'], $report_settlement_start_date);
            $item['settlement_end_date'] = $this->formatDate($item['currency'], $report_settlement_end_date);

            if ($item['deposit_date'] === '') {
                $deposit_date = $this->formatDate($item['currency'], $report_deposit_date);
            } else {
                $deposit_date = $this->formatDate($item['currency'], $item['deposit_date']);
            }

            $posted_date = '';
            if ($item['posted_date'] !== '') {
                $posted_date = $this->formatDate2($item['currency'], $item['posted_date']);
            }

            $posted_date_time = '';
            if ($item['posted_date_time'] !== '') {
                $posted_date_time = $this->formatDate($item['currency'], $item['posted_date_time']);
            }

            $item['deposit_date'] = $deposit_date;
            $item['posted_date'] = $posted_date;
            $item['posted_date_time'] = $posted_date_time;

            $item['merchant_id'] = $merchant_id;
            $item['merchant_store_id'] = $merchant_store_id;
            $item['report_id'] = $report_id;

            $new = $item;

            $transaction_type = $new['transaction_type'];

            if ($transaction_type === 'Order') {
                unset($new['amount']);
            }

            ksort($new);

            $md5_hash = md5(json_encode($new, JSON_THROW_ON_ERROR)); // TODO 注意hash的生成规则 有些类型不能排除

            if ($collection->offsetExists($md5_hash)) {
                //                $idx = $md5_hash_idx_map[$md5_hash] ?? 1;
                //                $md5_hash = md5(json_encode($new_item, JSON_THROW_ON_ERROR) . $idx);
                //                $md5_hash_idx_map[$md5_hash] = $idx + 1;

                if ($item['transaction_type'] === 'Order' || $item['transaction_type'] === 'other-transaction') {
                    $offset = $collection->offsetGet($md5_hash);
                    $offset['amount'] = bcadd($offset['amount'], $item['amount'], 4);
                    $offset['quantity_purchased'] = bcadd($offset['quantity_purchased'], $item['quantity_purchased']);
                    $collection->put($md5_hash, $offset);
                } else {
                    continue;
                }
            } else {
                $item['md5_hash'] = $md5_hash;
                $item['created_at'] = $cur_date;
                $item['updated_at'] = $cur_date;

                $collection->put($md5_hash, $item);
            }
        }
        $report_data_length = $collection->count();
        $console->notice(sprintf('报告ID:%s 开始处理数据. 数据长度:%s', $report_id, $report_data_length));
        $runtimeCalculator = new RuntimeCalculator();
        $runtimeCalculator->start();

        $collection->chunk(1000)->each(static function (Collection $list) use ($merchant_id, $merchant_store_id, $console): void {
            $page_date_length = $list->count();
            $console->info(sprintf('开始处理分页数据. 当前分页长度:%s', $list->count()));
            $runtimeCalculator = new RuntimeCalculator();
            $runtimeCalculator->start();

            $pluck = $list->pluck('md5_hash');
            $md5_hash_list = $pluck->toArray();

            $final = []; // 写入的数据集合
            $collections = AmazonSettlementReportDataFlatFileV2Model::query()->where('merchant_id', $merchant_id)
                ->where('merchant_store_id', $merchant_store_id)
                ->whereIn('md5_hash', $md5_hash_list)
                ->pluck('md5_hash');
            if ($collections->isEmpty()) {
                $final = $list->toArray();
            } else {
                $exist_md5_hash_list = $collections->toArray();
                $new_list = $list->pluck([], 'md5_hash')->toArray(); // 因为分片后索引了，需要重新修正集合的索引

                // 差集  需要插入的数据
                $array_diff = array_diff($md5_hash_list, $exist_md5_hash_list);
                if (count($array_diff)) {
                    foreach ($array_diff as $diff) {
                        $final[] = $new_list[$diff];
                    }
                }
            }
            $final_data_length = count($final);
            if ($final_data_length) {
                AmazonSettlementReportDataFlatFileV2Model::insert($final);
                // 当前分页数量与实际写入数量不相等时才需要高亮提示
                if ($page_date_length !== $final_data_length) {
                    $console->warning(sprintf('当前分页实际写入数据长度 %s.', $final_data_length));
                    $console->newLine();
                }
            }

            $console->info(sprintf('结束处理分页数据. 耗时:%s', $runtimeCalculator->stop()));
        });

        $console->notice(sprintf('报告ID:%s 结束处理数据. 耗时:%s', $report_id, $runtimeCalculator->stop()));

        return true;
    }

    public function getReportFileName(array $marketplace_ids, string $region, string $report_id = ''): string
    {
        return $report_id . '-' . $region;
    }

    private function formatDate($currency, $val, string $format = 'Y-m-d H:i:s'): string
    {
        if ($currency === 'USD') {
            $raw_format = 'Y-m-d H:i:s T';
        } elseif ($currency === 'CAD') {
            $raw_format = 'd.m.Y H:i:s T';
        } elseif ($currency === 'MXN') {
            $raw_format = 'd.m.Y H:i:s T';
        } elseif ($currency === 'EUR') {
            $raw_format = 'd.m.Y H:i:s T';
        } elseif ($currency === 'GBP') {
            $raw_format = 'd.m.Y H:i:s T';
        } else {
            return $val;
        }
        return Carbon::createFromFormat($raw_format, $val)->format($format);
    }

    private function formatDate2($currency, $val): string
    {
        $format = 'Y-m-d';
        if ($currency === 'USD') {
            return $val;
        }

        if ($currency === 'CAD') {
            $val = Carbon::createFromFormat('d.m.Y', $val)->format($format);
        } elseif ($currency === 'MXN') {
            $val = Carbon::createFromFormat('d.m.Y', $val)->format($format);
        } elseif ($currency === 'EUR') {
            $val = Carbon::createFromFormat('d.m.Y', $val)->format($format);
        } elseif ($currency === 'GBP') {
            $val = Carbon::createFromFormat('d.m.Y', $val)->format($format);
        } else {
            return $val;
        }
        return $val;
    }
}
