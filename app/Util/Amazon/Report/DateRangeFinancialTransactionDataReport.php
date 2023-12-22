<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Util\Amazon\Report;

use App\Model\AmazonReportDateRangeFinancialTransactionDataModel;
use App\Util\ConsoleLog;
use App\Util\Log\AmazonReportDocumentLog;
use App\Util\RuntimeCalculator;
use Carbon\Carbon;
use Hyperf\Collection\Collection;
use Hyperf\Context\ApplicationContext;
use function Hyperf\Config\config;

class DateRangeFinancialTransactionDataReport extends ReportBase
{
    /**
     * @param string $report_id
     * @param string $file
     * @throws \JsonException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @return bool
     */
    public function run(string $report_id, string $file): bool
    {

        $logger = ApplicationContext::getContainer()->get(AmazonReportDocumentLog::class);
        $console = ApplicationContext::getContainer()->get(ConsoleLog::class);

        $merchant_id = $this->getMerchantId();
        $merchant_store_id = $this->getMerchantStoreId();

        $headers_map = $this->getHeaderMap();
        $currency_list = array_keys($headers_map);

        $lineNumber = 2; // 指定的行号
        $splFileObject = new \SplFileObject($file, 'r');
        $splFileObject->seek($lineNumber - 1); // 转到指定行号的前一行
        $desiredLine = $splFileObject->current(); // 获取指定行的内容

        $config = [];
        $currency = '';
        foreach ($currency_list as $_currency) {
            if (str_contains($desiredLine, $_currency)) {
                $config = $headers_map[$_currency] ?? [];//选择使用哪个货币对应的表头映射关系
                $currency = $_currency;
                break;
            }
        }
        if ($currency === '') {
            $log = sprintf('merchant_id:%s merchant_store_id:%s report_id:%s currency:%s 无法解析当前报告对应的货币，请检查.', $merchant_id, $merchant_store_id, $report_id, $currency);
            $console->error($log);
            $logger->error($log);
            return true;
        }
        if (count($config) === 0) {
            //请定义该货币对应的表头映射关系
            $log = sprintf('merchant_id:%s merchant_store_id:%s report_id:%s currency:%s 无法找到当前货币对应的表头映射关系', $merchant_id, $merchant_store_id, $report_id, $currency);
            $console->error($log);
            $logger->error($log);
            return true;
        }

        //日期统一转换为UTC时区
        if ($currency === 'USD' || $currency === 'CAD') {
            //英语
            $locale = 'en';
            //解析类似 Jun 26, 2023 11:53:30 PM PDT 格式时间
            $locale_format = 'F j, Y H:i:s A T';
        } else if ($currency === 'MXN') {
            //西班牙语
            $locale = 'es';
            //解析类似 12 abr 2023 16:26:06 GMT-7 格式时间
            $locale_format = 'd F Y H:i:s \G\M\TO';
        } else {
            $log = sprintf('merchant_id:%s merchant_store_id:%s report_id:%s currency:%s 无法为当前报告解析日期语言，请检查', $merchant_id, $merchant_store_id, $report_id, $currency);
            $console->error($log);
            $logger->error($log);
            return true;
        }

        $handle = fopen($file, 'rb');
        // 前8行都是表头数据和报告描述信息，直接丢弃
        for ($i = 0; $i < 7; ++$i) {
            fgets($handle);
        }

        // 处理映射关系
        $explodes = explode(',', str_replace("\r\n", '', fgets($handle)));
        $headers = array_map(static function ($val) {
            return trim(str_replace('"', '', $val));
        }, $explodes);

        $map = [];
        foreach ($headers as $index => $header) {
            if (! isset($config[$header])) {
                continue;
            }
            $map[$index] = $config[$header];
        }

        $cur_date = Carbon::now()->format('Y-m-d H:i:s');
        $collection = new Collection();

        //非英语地区的需要作语言转换
        $report_lang = config('amazon.report_lang.' . $currency);

        $md5_hash_idx_map = [];
        while (! feof($handle)) {
            $fgets = fgets($handle);
            if ($fgets === false) {
                break;
            }

            $row = str_replace(["\r\n", ',,'], ['', ',"",'], trim($fgets));
            $explodes = explode('","', $row);
            $new = array_map(static function ($val) {
                return trim(str_replace('"', '', $val));
            }, $explodes);
            $item = [];
            foreach ($map as $index => $value) {
                if (! isset($new[$index])) {
                    $console->error(sprintf('列不存在:%s merchant_id:%s merchant_store_id:%s file:%s', $index, $merchant_id, $merchant_store_id, $file));
                    continue;
                }
                $val = $new[$index];
                if ($value === 'date') {
                    $localeDate = Carbon::createFromLocaleFormat($locale_format, $locale, $val);
                    $val = $localeDate->utc()->format('Y-m-d H:i:s');
                } else if (($value === 'quantity') && $val === '') {
                    $val = 0;
                } else if ($value === 'type') {
                    if (! is_null($report_lang) && isset($report_lang[$val])) {
                        $val = $report_lang[$val];
                    }
                } else if ($value === 'description') {
                    if (! is_null($report_lang) && isset($report_lang[$val])) {
                        $val = $report_lang[$val];
                    }
                }
                $item[$value] = $val;
            }

            $item['merchant_id'] = $merchant_id;
            $item['merchant_store_id'] = $merchant_store_id;
            $item['currency'] = $currency;
            $item['report_id'] = $report_id;

            $new_item = $item;
            ksort($new_item);

            $md5_hash = md5(json_encode($new_item, JSON_THROW_ON_ERROR));//TODO 注意hash的生成规则

            if ($collection->offsetExists($md5_hash)) {
                $idx = $md5_hash_idx_map[$md5_hash] ?? 1;
                $md5_hash = md5(json_encode($new_item, JSON_THROW_ON_ERROR) . $idx);
                $md5_hash_idx_map[$md5_hash] = $idx + 1;
            }
            $item['md5_hash'] = $md5_hash;
            $item['created_at'] = $cur_date;
            $item['updated_at'] = $cur_date;

            $collection->push($item);

        }
        fclose($handle);

        $report_data_length = $collection->count();
        $console->notice(sprintf('报告ID:%s 开始处理数据. 数据长度:%s', $report_id, $report_data_length));
        $runtimeCalculator = new RuntimeCalculator();
        $runtimeCalculator->start();

        // 数据分片处理
        $collection->chunk(1000)->each(static function (Collection $list) use ($merchant_id, $merchant_store_id, $console): void {

            $page_date_length = $list->count();
            $console->info(sprintf('开始处理分页数据. 当前分页长度:%s', $page_date_length));
            $runtimeCalculator = new RuntimeCalculator();
            $runtimeCalculator->start();

            $pluck = $list->pluck('md5_hash');
            $md5_hash_list = $pluck->toArray();

            $final = []; // 写入的数据集合
            $collections = AmazonReportDateRangeFinancialTransactionDataModel::query()->where('merchant_id', $merchant_id)
                ->where('merchant_store_id', $merchant_store_id)
                ->whereIn('md5_hash', $md5_hash_list)
                ->pluck('md5_hash');
            if ($collections->isEmpty()) {
                $final = $list->toArray();
            } else {
                $exist_md5_hash_list = $collections->toArray();
                $new_list = $list->pluck([], 'md5_hash')->toArray();//因为分片后索引了，需要重新修正集合的索引

                //差集  需要插入的数据
                $array_diff = array_diff($md5_hash_list, $exist_md5_hash_list);
                if (count($array_diff)) {
                    foreach ($array_diff as $diff) {
                        $final[] = $new_list[$diff];
                    }
                }
            }

            $final_data_length = count($final);
            if ($final_data_length) {
                AmazonReportDateRangeFinancialTransactionDataModel::insert($final);
                //当前分页数量与实际写入数量不相等时才需要高亮提示
                if ($page_date_length !== $final_data_length) {
                    $console->warning(sprintf('当前分页实际写入数据长度 %s.', $final_data_length));
                    $console->newLine();
                }
            }
            $console->info(sprintf('结束处理分页数据. 耗时:%s', $runtimeCalculator->stop()));
            $console->newLine();
        });

        $console->notice(sprintf('报告ID:%s 结束处理数据. 耗时:%s', $report_id, $runtimeCalculator->stop()));

        $real_data_length = AmazonReportDateRangeFinancialTransactionDataModel::where('merchant_id', $merchant_id)
            ->where('merchant_store_id', $merchant_store_id)
            ->where('report_id', $report_id)
            ->count();
        if ($report_data_length !== $real_data_length) {
            $log = sprintf('日期范围报告 merchant_id:%s merchant_store_id:%s 报告ID:%s 报告数据长度:%s, 数据库数据长度:%s 两者数据长度不一致，请检查.', $merchant_id, $merchant_store_id, $report_id, $report_data_length, $real_data_length);
            $console->error($log);
            $logger->error($log);
        }

        return true;
    }
}
