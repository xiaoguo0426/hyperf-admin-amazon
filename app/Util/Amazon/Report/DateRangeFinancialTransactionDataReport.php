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
use AmazonPHP\SellingPartner\Marketplace;
use App\Model\AmazonReportDateRangeFinancialTransactionDataModel;
use App\Util\Amazon\Report\Runner\ReportRunnerInterface;
use App\Util\Amazon\Report\Runner\ScheduledReportRunner;
use App\Util\ConsoleLog;
use App\Util\Constants;
use App\Util\Log\AmazonReportDocumentLog;
use App\Util\RuntimeCalculator;
use Carbon\Carbon;
use Hyperf\Collection\Collection;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\StdoutLoggerInterface;
use JsonException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use function Hyperf\Config\config;

class DateRangeFinancialTransactionDataReport extends ReportBase
{
    /**
     *
     * @param ScheduledReportRunner $reportRunner
     * @throws JsonException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @return bool
     */
    public function run(ReportRunnerInterface $reportRunner): bool
    {

        $logger = ApplicationContext::getContainer()->get(AmazonReportDocumentLog::class);
        $console = ApplicationContext::getContainer()->get(ConsoleLog::class);

        $merchant_id = $this->getMerchantId();
        $merchant_store_id = $this->getMerchantStoreId();

        $file = $reportRunner->getReportFilePath();
        $report_id = $reportRunner->getReportDocumentId();
        $marketplace_ids = $reportRunner->getMarketplaceIds();

        if (count($marketplace_ids) > 1) {
            $log = sprintf('merchant_id:%s merchant_store_id:%s report_id:%s GET_DATE_RANGE_FINANCIAL_TRANSACTION_DATA 报告存在多个地区，请检查.', $merchant_id, $merchant_store_id, $report_id);
            $console->error($log);
            $logger->error($log);

            return true;
        }
        //取第一个marketplace_id。目前只发现该报告返回的数据中只会有一个marketplace_id。EU地区数据存在没办法通过解析报表内容中的currency，进而映射对应的表头，所以改为使用country_code映射
        $marketplace_id = $marketplace_ids[0];
        try {
            $country_code = Marketplace::fromId($marketplace_id)->countryCode();
        } catch (InvalidArgumentException $e) {
            return true;
        }

        $all_header_map = $this->getHeaderMap();

        $lineNumber = 2; // 指定的行号
        $splFileObject = new \SplFileObject($file, 'r');
        $splFileObject->seek($lineNumber - 1); // 转到指定行号的前一行
        $desiredLine = $splFileObject->current(); // 获取指定行的内容

        if (! isset($all_header_map[$country_code])) {
            //请定义该国家对应的表头映射关系
            $log = sprintf('merchant_id:%s merchant_store_id:%s report_id:%s currency:%s GET_DATE_RANGE_FINANCIAL_TRANSACTION_DATA 无法找到国家对应的表头映射关系', $merchant_id, $merchant_store_id, $report_id, $country_code);
            $console->error($log);
            $logger->error($log);
            return true;
        }

        $config = $all_header_map[$country_code];
        if (empty($config)) {
            //请定义该国家对应的表头映射关系
            $log = sprintf('merchant_id:%s merchant_store_id:%s report_id:%s currency:%s GET_DATE_RANGE_FINANCIAL_TRANSACTION_DATA 请完善当前国家对应的表头映射关系', $merchant_id, $merchant_store_id, $report_id, $country_code);
            $console->error($log);
            $logger->error($log);
            return true;
        }

        //日期统一转换为UTC时区
        if ($country_code === 'US' || $country_code === 'CA') {
            //英语
            $locale = 'en';
            //解析类似 Jun 26, 2023 11:53:30 PM PDT 格式时间
            $locale_format = 'F j, Y H:i:s A T';
        } else if ($country_code === 'MX') {
            //西班牙语
            $locale = 'es';
            //解析类似 12 abr 2023 16:26:06 GMT-7 格式时间
            $locale_format = 'd F Y H:i:s \G\M\TO';
        } else if ($country_code === 'DE') {
            $locale = 'en';
            //解析类似 17.11.2023 10:50:20 UTC 格式时间
            $locale_format = 'd.m.Y H:i:s T';
        } else if ($country_code === 'FR') {

            $locale = 'fr';
            //解析类似 9 nov. 2023 21:44:58 UTC 格式时间
            $locale_format = 'j M Y H:i:s T';
        } else if ($country_code === 'GB') {
            $locale = 'en';
            //解析类似 20 Nov 2023 05:10:24 UTC 格式时间
            $locale_format = 'd M Y H:i:s T';
        } else if ($country_code === 'ES') {
            $locale = 'es';
            //解析类似 20 Nov 2023 05:10:24 UTC 格式时间
            $locale_format = 'd F Y H:i:s T';
        } else if ($country_code === 'IT') {
            $locale = 'it';
            //解析类似 20 Nov 2023 05:10:24 UTC 格式时间
            $locale_format = 'd M Y H:i:s T';
        } else {
            $log = sprintf('merchant_id:%s merchant_store_id:%s report_id:%s currency:%s 无法为当前报告解析日期语言，请检查', $merchant_id, $merchant_store_id, $report_id, $country_code);
            $console->error($log);
            $logger->error($log);

            return true;
        }

        $handle = fopen($file, 'rb');
        //前8行都是表头数据和报告描述信息，直接丢弃
        for ($i = 0; $i < 7; $i++) {
            fgets($handle);
        }

        //处理映射关系
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
        $report_lang = config('amazon.report_lang.' . $country_code);

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
//                    var_dump($val);
                    $localeDate = Carbon::createFromLocaleFormat($locale_format, $locale, $val);
                    $val = $localeDate->utc()->format('Y-m-d H:i:s');
//                    var_dump($val);
                } else if (($value === 'quantity') && $val === '') {
                    $val = 0;
                } else if ($value === 'type') {
                    if (! is_null($report_lang)) {
                        //type属性必须要转换
                        if (isset($report_lang[$val])) {
                            $val = $report_lang[$val];
                        } else {
                            //直接跳过处理并作日志记录
//                            $log = sprintf('当前语言转换失败，请补充定义:%s . merchant_id:%s merchant_store_id:%s country_code:%s marketplace_id:%s file:%s', $val, $merchant_id, $merchant_store_id, $country_code, $marketplace_id, $file);
//                            $console->error($log);
//                            $logger->error($log);
                            return true;
                        }
                    }
                } else if (($value === 'description') && ! is_null($report_lang) && isset($report_lang[$val])) {
                    $val = $report_lang[$val];
                }
                $item[$value] = $val;
            }

            //如果type为空字符串切description已Save开头，则type赋值为Coupon
            if (($item['type'] === '') && str_starts_with($item['description'], 'Save')) {
                $item['type'] = 'CouponRedemptionFee';
            }

            $item['merchant_id'] = $merchant_id;
            $item['merchant_store_id'] = $merchant_store_id;
            $item['marketplace_id'] = $marketplace_id;
            $item['country_code'] = $country_code;
            $item['currency'] = Constants::COUNTRY_CURRENCY_MAP[$country_code] ?? '';
            $item['report_id'] = $report_id;

            $new_item = $item;
            unset($new_item['other'], $new_item['total'], $new_item['report_id']);
            ksort($new_item);

            $md5_hash = md5(json_encode($new_item, JSON_THROW_ON_ERROR));//TODO 注意hash的生成规则

            if ($collection->offsetExists($md5_hash)) {
                //如果md5相同的话，合并other,total值
                $exist_item = $collection->offsetGet($md5_hash);
                $exist_item['other'] = bcadd($exist_item['other'], $item['other'], 4);
                $exist_item['total'] = bcadd($exist_item['total'], $item['total'], 4);

                $collection->put($md5_hash, $exist_item);
            } else {
                $item['md5_hash'] = $md5_hash;
                $item['created_at'] = $cur_date;
                $item['updated_at'] = $cur_date;

                $collection->put($md5_hash, $item);
            }

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

//        $real_data_length = AmazonReportDateRangeFinancialTransactionDataModel::where('merchant_id', $merchant_id)
//            ->where('merchant_store_id', $merchant_store_id)
//            ->where('report_id', $report_id)
//            ->count();
//        if ($report_data_length !== $real_data_length) {
//            $log = sprintf('日期范围报告 merchant_id:%s merchant_store_id:%s 报告ID:%s 报告数据长度:%s, 数据库数据长度:%s 两者数据长度不一致，请检查.', $merchant_id, $merchant_store_id, $report_id, $report_data_length, $real_data_length);
//            $console->error($log);
//            $logger->error($log);
//        }

        return true;
    }

    /**
     * @param array $marketplace_ids
     * @param string $report_id
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @return bool
     */
    public function checkMarketplaceIds(array $marketplace_ids, string $report_id): bool
    {
        $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);

        $logger = ApplicationContext::getContainer()->get(AmazonReportDocumentLog::class);

        $is_error_marketplace_id_flag = false;
        $real_marketplace_ids_count = count($marketplace_ids);
        if ($real_marketplace_ids_count > 1) {
            $is_error_marketplace_id_flag = true;
            $log = sprintf('merchant_id:%s merchant_store_id:%s region:%s report_document_id:%s 报告存在多个区域，请检查. 报告已跳过处理.', $this->merchant_id, $this->merchant_store_id, $this->region, $report_id);
            $console->error($log);
            $logger->error($log);
        } else if ($real_marketplace_ids_count === 0) {
            $is_error_marketplace_id_flag = true;
            $log = sprintf('merchant_id:%s merchant_store_id:%s region:%s report_document_id:%s 报告不存在区域，请检查. 报告已跳过处理.', $this->merchant_id, $this->merchant_store_id, $this->region, $report_id);
            $console->error($log);
            $logger->error($log);
        }
        return $is_error_marketplace_id_flag;

    }
}
