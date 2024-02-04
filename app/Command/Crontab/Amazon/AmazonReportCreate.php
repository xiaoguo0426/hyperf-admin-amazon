<?php

namespace App\Command\Crontab\Amazon;

use AmazonPHP\SellingPartner\AccessToken;
use AmazonPHP\SellingPartner\Exception\ApiException;
use AmazonPHP\SellingPartner\Exception\InvalidArgumentException;
use AmazonPHP\SellingPartner\Model\Reports\CreateReportSpecification;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use App\Queue\AmazonGetReportQueue;
use App\Queue\Data\AmazonGetReportData;
use App\Util\Amazon\Report\ReportBase;
use App\Util\Amazon\Report\ReportFactory;
use App\Util\AmazonApp;
use App\Util\AmazonSDK;
use App\Util\ConsoleLog;
use App\Util\Log\AmazonReportCreateLog;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Context\ApplicationContext;
use Hyperf\Di\Exception\NotFoundException;
use JsonException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use function Hyperf\Config\config;
use function Hyperf\Support\make;

#[Command]
class AmazonReportCreate extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('crontab:amazon:report-create');
        // 指令配置
        $this->setDescription('Crontab Amazon Report Create Command');
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws NotFoundException
     * @throws \RedisException
     * @throws ContainerExceptionInterface
     */
    public function handle(): void
    {
        AmazonApp::each(static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) {
            $logger = di(AmazonReportCreateLog::class);
            $console = ApplicationContext::getContainer()->get(ConsoleLog::class);

            $report_types = config('amazon_reports.requested');
            foreach ($report_types as $report_type) {

                $instance = ReportFactory::getInstance($merchant_id, $merchant_store_id, $region, $report_type);

                $instance->requestReport($marketplace_ids, static function (ReportBase $instance, $report_type, CreateReportSpecification $body, array $marketplace_ids) use ($sdk, $accessToken, $region, $logger, $merchant_id, $merchant_store_id) {
                    $console = ApplicationContext::getContainer()->get(ConsoleLog::class);

                    // 注意匿名函数里的$marketplace_ids的值
                    if ($instance->checkDir() === false) {
                        $console->error('报告保存路径有误，请检查 ' . $instance->getDir());
                        return true;
                    }
                    $dir = $instance->getDir();

                    $file_base_name = $instance->getReportFileName($marketplace_ids, $region);
                    $file_path = $dir . $file_base_name . $instance->getFileExt();
                    if ($instance->checkReportFile($marketplace_ids, $region)) {
                        //减少每个周期创建报告的请求
                        //再判断每个类型的报告是否需要再请求 -- 可能上一个周期生成了报告文件，但数据可能不完整。需要每个类型报告自行判断是否需要再次生成
                        try {
                            $check = $instance->checkReportContent($file_path);
                            if ($check === true) {
                                // 文件存在了直接返回
                                $console->notice(sprintf('Create %s 类型报告文件已存在，不需要重复创建. merchant_id: %s merchant_store_id: %s file_path:%s ', $report_type, $merchant_id, $merchant_store_id, $file_path));
                                //不需要重新创建
                                return true;
                            }
                        } catch (JsonException $jsonException) {
                            $console->warning(sprintf('Create Report Warning. %s checkReportContent解析报告内容出错 merchant_id: %s merchant_store_id: %s file_path:%s', $report_type, $merchant_id, $merchant_store_id, $file_path), [
                                'message' => $jsonException->getMessage(),
                                'data_start_time' => $body->getDataStartTime(),
                                'data_end_time' => $body->getDataEndTime()
                            ]);
                        }
                        return true;
                    }

                    //需要创建的报告类型再次检测是否被标记了删除，如果已被标记，则不再创建
                    if ($instance->checkMarkCanceled($marketplace_ids)) {
                        $log = sprintf('Create Report Notice. %s 类型报告文件被标注删除，不需要重复创建. merchant_id: %s merchant_store_id: %s region:%s marketplace_ids:%s data_start_time:%s data_end_time:%s ', $report_type, $merchant_id, $merchant_store_id, $region, implode(',', $marketplace_ids), is_null($body->getDataStartTime()) ? '' : $body->getDataStartTime()->format('Y-m-d H:i:s'), is_null($body->getDataEndTime()) ? '' : $body->getDataEndTime()->format('Y-m-d H:i:s'));
                        $console->comment($log);
                        $logger->notice($log);
                        return true;
                    }

                    $retry = 10;

                    $data_start_time = $body->getDataStartTime() ? $body->getDataStartTime()->format('Y-m-d H:i:s') : '';
                    $data_end_time = $body->getDataEndTime() ? $body->getDataEndTime()->format('Y-m-d H:i:s') : '';

                    $queue = new AmazonGetReportQueue();

                    while (true) {
                        try {
                            $response = $sdk->reports()->createReport($accessToken, $region, $body);
                            $report_id = $response->getReportId();

                            /**
                             * @var  AmazonGetReportData $amazonGetReportData
                             */
                            $amazonGetReportData = make(AmazonGetReportData::class);
                            $amazonGetReportData->setMerchantId($merchant_id);
                            $amazonGetReportData->setMerchantStoreId($merchant_store_id);
                            $amazonGetReportData->setRegion($region);
                            $amazonGetReportData->setMarketplaceIds($marketplace_ids);
                            $amazonGetReportData->setReportId($report_id);
                            $amazonGetReportData->setReportType($report_type);
                            $amazonGetReportData->setDataStartTime($data_start_time);
                            $amazonGetReportData->setDataEndTime($data_end_time);

                            $log = sprintf('Create %s report_id: %s merchant_id: %s merchant_store_id: %s region:%s start_time:%s end_time:%s', $report_type, $report_id, $merchant_id, $merchant_store_id, $region, $data_start_time, $data_end_time);
                            $console->info($log);
                            $logger->info($log, [
                                'marketplace_ids' => $marketplace_ids,
                                'data_start_time' => $data_start_time,
                                'data_end_time' => $data_end_time,
                            ]);

                            $queue->push($amazonGetReportData);

                            break;
                        } catch (ApiException $e) {
                            var_dump($e->getResponseBody());
                            --$retry;
                            if ($retry > 0) {
                                $console->warning(sprintf('Create %s report fail, retry: %s  merchant_id: %s merchant_store_id: %s region:%s', $report_type, $retry, $merchant_id, $merchant_store_id, $region));
                                sleep(5);
                                continue;
                            }
                            $logger->error(sprintf('ApiException %s 创建报告出错 merchant_id: %s merchant_store_id: %s region:%s', $report_type, $merchant_id, $merchant_store_id, $region), [
                                'message' => $e->getMessage(),
                                'response body' => $e->getResponseBody(),
                                'data_start_time' => $body->getDataStartTime(),
                                'data_end_time' => $body->getDataEndTime(),
                            ]);
                            break;
                        } catch (InvalidArgumentException $e) {
                            $logger->error(sprintf('InvalidArgumentException %s 创建报告出错 merchant_id: %s merchant_store_id: %s region:%s', $report_type, $merchant_id, $merchant_store_id, $region));
                            break;
                        }
                    }

                    sleep(5);
                    return true;
                });

            }

        });
    }
}