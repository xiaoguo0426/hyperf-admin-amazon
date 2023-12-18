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
use App\Util\RuntimeCalculator;
use Carbon\Carbon;
use Hyperf\Collection\Collection;
use Hyperf\Context\ApplicationContext;

class DateRangeFinancialTransactionDataReport extends ReportBase
{
    public function run(string $report_id, string $file): bool
    {
        $headers_map = $this->getHeaderMap();
        $currency_list = array_keys($headers_map);

        $lineNumber = 2; // 指定的行号
        $splFileObject = new \SplFileObject($file, 'r');
        $splFileObject->seek($lineNumber - 1); // 转到指定行号的前一行
        $desiredLine = $splFileObject->current(); // 获取指定行的内容

        $config = [];
        $currency = '';
        foreach ($currency_list as $currency) {
            if (str_contains($desiredLine, $currency)) {
                $config = $headers_map[$currency]; // 选择使用哪个货币对应的表头映射关系
                break;
            }
        }
        if (count($config) === 0) {
            // 请定义该货币对应的表头映射关系
            return true;
        }

        $locale = 'en';
        if ($currency === 'MXN') {
            // 时间解析语言
            $locale = 'es'; // 西班牙语
        }

        $merchant_id = $this->getMerchantId();
        $merchant_store_id = $this->getMerchantStoreId();

        //        $logger = ApplicationContext::getContainer()->get(AmazonReportDocumentLog::class);
        $console = ApplicationContext::getContainer()->get(ConsoleLog::class);

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
                //                if (! isset($new[$index])) {
                //                    var_dump($new);
                //                    $console->error(sprintf('列不存在:%s merchant_id:%s merchant_store_id:%s file:%s', $index, $merchant_id, $merchant_store_id, $file));
                //                    die();
                //                    continue;
                //                }
                $val = $new[$index];
                if ($value === 'date') {
                    if ($locale === 'es') {
                        // 解析类似 12 abr 2023 16:26:06 GMT-7 格式时间
                        $val = Carbon::createFromLocaleFormat('d F Y H:i:s \G\M\TO', $locale, $val)->format('Y-m-d H:i:s');
                    } else {
                        // 解析类似 Jun 26, 2023 11:53:30 PM PDT 格式时间
                        $val = Carbon::createFromLocaleFormat('F j, Y H:i:s A T', $locale, $val)->format('Y-m-d H:i:s');
                    }
                } elseif (($value === 'quantity') && $val === '') {
                    $val = 0;
                }

                $item[$value] = $val;
            }

            $item['merchant_id'] = $merchant_id;
            $item['merchant_store_id'] = $merchant_store_id;
            $item['created_at'] = $cur_date;
            $item['updated_at'] = $cur_date;
            $item['report_id'] = $report_id;
            $collection->push($item);
        }
        fclose($handle);

        $console->notice(sprintf('报告ID:%s 开始处理数据. 数据长度:%s', $report_id, $collection->count()));
        $runtimeCalculator = new RuntimeCalculator();
        $runtimeCalculator->start();

        try {
            // 数据分片处理
            $collection->chunk(1000)->each(static function (Collection $list) use ($console): void {
                $console->info(sprintf('开始处理分页数据. 当前分页长度:%s', $list->count()));
                $runtimeCalculator = new RuntimeCalculator();
                $runtimeCalculator->start();

                try {
                    $final = []; // 写入的数据集合
                    foreach ($list as $item) {
                        $settlement_id = $item['settlement_id'];
                        $type = $item['type'];
                        $order_id = $item['order_id'];
                        $sku = $item['sku'];
                        $description = $item['description'];
                        $report_id = $item['report_id'];

                        //                        $model = AmazonReportDateRangeFinancialTransactionDataModel::query()
                        //                            ->where('merchant_id', $merchant_id)
                        //                            ->where('merchant_store_id', $merchant_store_id)
                        //                            ->where('settlement_id', $settlement_id);
                        //
                        //                        if ($type === '' && $sku === '') {
                        //                            // 优惠券类型付款报告数据，需要补充date条件。
                        //                            $model->where('order_id', $order_id)
                        //                                ->where('date', $item['date'])
                        //                                ->where('report_id', $report_id);
                        //                        } elseif ($type === 'Service Fee') {
                        //                            $model->where('type', $type)
                        //                                ->where('order_id', $order_id)
                        //                                ->where('description', $item['description'])
                        //                                ->where('date', $item['date']);
                        //                        } elseif ($type === 'Fee Adjustment' && $sku === '') {
                        //                            $model->where('type', $type)
                        //                                ->where('order_id', $order_id)
                        //                                ->where('total', $item['total']);
                        //                        } elseif ($type === 'Order') {
                        //                            $model->where('type', $type)
                        //                                ->where('order_id', $order_id)
                        //                                ->where('sku', $sku)
                        //                                ->where('date', $item['date']);
                        //                        } elseif ($type === 'FBA Inventory Fee') {
                        //                            $model->where('type', $type)
                        //                                ->where('order_id', $order_id)
                        //                                ->where('description', $description)
                        //                                ->where('date', $item['date'])
                        //                                ->where('total', $item['total']);
                        //                        } elseif ($type === 'FBA Customer Return Fee') {
                        //                            $model->where('type', $type)
                        //                                ->where('order_id', $order_id)
                        //                                ->where('sku', $sku)
                        //                                ->where('description', $description)
                        //                                ->where('date', $item['date']);
                        //                        } elseif ($type === 'Refund') {
                        //                            $model->where('type', $type)
                        //                                ->where('order_id', $order_id)
                        //                                ->where('sku', $sku)
                        //                                ->where('description', $description)
                        //                                ->where('date', $item['date']);
                        //                        } else if ($type === 'Adjustment') {
                        //                            $model->where('type', $type)
                        //                                ->where('order_id', $order_id)
                        //                                ->where('sku', $sku)
                        //                                ->where('description', $description)
                        //                                ->where('date', $item['date']);
                        //                        } else if ($type === 'Order_Retrocharge') {
                        //                            $model->where('type', $type)
                        //                                ->where('order_id', $order_id)
                        //                                ->where('description', $description)
                        //                                ->where('date', $item['date']);
                        //                        } else if ($type === 'Liquidations') {
                        //                            $model->where('type', $type)
                        //                                ->where('order_id', $order_id)
                        //                                ->where('sku', $sku);
                        //                        } else if ($type === 'Transfer') {
                        //                            $model->where('type', $type)
                        //                                ->where('description', $description)
                        //                                ->where('date', $item['date']);
                        //                        } else if ($type === 'Deal Fee') {
                        //                            $model->where('type', $type)
                        //                                ->where('order_id', $order_id)
                        //                                ->where('description', $description);
                        //                        } else if ($type === 'Refund_Retrocharge') {
                        //                            $model->where('type', $type)
                        //                                ->where('order_id', $order_id)
                        //                                ->where('description', $description);
                        //                        } else if ($type === 'Debt') {
                        //                            $model->where('type', $type)
                        //                                ->where('description', $description)
                        //                                ->where('date', $item['date']);
                        //                        } else if ($type === 'Chargeback Refund') {
                        //                            $model->where('type', $type)
                        //                                ->where('order_id', $order_id)
                        //                                ->where('sku', $sku);
                        // //                                ->where('description', $description);
                        //                        } else {
                        //                            $log = sprintf('日期范围财务报告 未知类型 %s. 请检查数据.已跳过处理 report_id:%s', $type, $report_id);
                        //                            $console->error($log);
                        //                            continue;
                        //                        }
                        //
                        //
                        //                        $collection = $model->first();
                        //                        if (is_null($collection)) {
                        //                            $final[] = $item;
                        //                        }

                        $final[] = $item;
                    }
                    if (count($final) > 0) {
                        AmazonReportDateRangeFinancialTransactionDataModel::insert($final);
                    }
                } catch (\Exception $exception) {
                    var_dump($exception->getMessage());
                }

                $console->info(sprintf('结束处理分页数据. 耗时:%s', $runtimeCalculator->stop()));
            });
        } catch (\RuntimeException $runtimeException) {
            $console->error(sprintf('file:%s 处理失败. %s', $file, $runtimeException->getMessage()));
            // 一旦出错，直接删除该文件，下一次重新拉取
            //            unlink($file);
        }

        $console->notice(sprintf('报告ID:%s 结束处理数据. 耗时:%s', $report_id, $runtimeCalculator->stop()));

        return true;
    }
}
