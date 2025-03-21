<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Util\Amazon\Report;

use AmazonPHP\SellingPartner\Marketplace;
use AmazonPHP\SellingPartner\Model\Reports\CreateReportSpecification;
use App\Util\Amazon\Report\Runner\ReportRunnerInterface;
use App\Util\Amazon\Report\Runner\RequestedReportRunner;
use App\Util\Log\AmazonReportActionLog;
use App\Util\RedisHash\AmazonAsinSaleVolumeHash;
use Carbon\Carbon;
use Hyperf\Context\ApplicationContext;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use function Hyperf\Support\make;

class SalesAndTrafficReportCustom extends ReportBase
{
    private array $date_list;

    public function __construct(int $merchant_id, int $merchant_store_id, string $region, string $report_type)
    {
        parent::__construct(${$merchant_id}, $merchant_store_id, $region, $report_type);

        $last_end_time = Carbon::now('UTC')->subDays(2)->format('Y-m-d 23:59:59');
        $last_3days_start_time = Carbon::now('UTC')->subDays(4)->format('Y-m-d 00:00:00'); // 最近3天
        $last_7days_start_time = Carbon::now('UTC')->subDays(8)->format('Y-m-d 00:00:00'); // 最近7天
        $last_14days_start_time = Carbon::now('UTC')->subDays(15)->format('Y-m-d 00:00:00'); // 最近14天
        $last_31days_start_time = Carbon::now('UTC')->subDays(31)->format('Y-m-d 00:00:00'); // 最近30天

        $this->date_list = [
            'last_3days' => [
                'start_time' => $last_3days_start_time,
                'end_time' => $last_end_time,
            ],
            'last_7days' => [
                'start_time' => $last_7days_start_time,
                'end_time' => $last_end_time,
            ],
            'last_14days' => [
                'start_time' => $last_14days_start_time,
                'end_time' => $last_end_time,
            ],
            'last_30days' => [
                'start_time' => $last_31days_start_time,
                'end_time' => $last_end_time,
            ],
        ];

        $this->setReportType('GET_SALES_AND_TRAFFIC_REPORT');
    }

    /**
     * @param RequestedReportRunner $reportRunner
     * @throws \JsonException
     * @throws \RedisException
     * @return bool
     */
    public function run(ReportRunnerInterface $reportRunner): bool
    {
        $merchant_id = $this->getMerchantId();
        $merchant_store_id = $this->getMerchantStoreId();

        $file = $reportRunner->getReportFilePath();
//        $report_id = $reportRunner->getReportId();
        $report_type = $reportRunner->getReportType();

        $logger = di(AmazonReportActionLog::class);

        $content = file_get_contents($file);

        $json = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        $reportStartDate = $this->getReportStartDate();
        $reportEndDate = $this->getReportEndDate();

        if (is_null($reportStartDate)) {
            $logger->error(sprintf('Action %s 开始时间有误 merchant_id: %s merchant_store_id: %s file:%s', $report_type, $merchant_id, $merchant_store_id, $file));
            return true;
        }
        if (is_null($reportEndDate)) {
            $logger->error(sprintf('Action %s 结束时间有误 merchant_id: %s merchant_store_id: %s file:%s', $report_type, $merchant_id, $merchant_store_id, $file));
            return true;
        }

        $startDate = Carbon::createFromFormat('Ymd', $reportStartDate->format('Ymd'), 'UTC');
        $endDate = Carbon::createFromFormat('Ymd', $reportEndDate->format('Ymd'), 'UTC');

        $diff = $endDate->diff($startDate);
        $diff_days = $diff->days + 1;

        // 报告更多参数，请见SalesAndTrafficReport类
        //        $data_time = $json['reportSpecification']['dataStartTime'];
        $marketplace_id = $json['reportSpecification']['marketplaceIds'][0];
        // 目前销量只统计US
        $us_marketplace_id = Marketplace::US()->id();
        if ($marketplace_id !== $us_marketplace_id) {
            return true;
        }

        $salesAndTrafficByAsin = $json['salesAndTrafficByAsin'];

        $type = sprintf('last_%sdays', $diff_days);

        /**
         * @var AmazonAsinSaleVolumeHash $hash
         */
        $hash = make(AmazonAsinSaleVolumeHash::class, [$merchant_id, $type]);
        $hash->destroy();

        $asin_maps = [];
        foreach ($salesAndTrafficByAsin as $salesAndTraffic) {
            $childAsin = $salesAndTraffic['childAsin'];

            $salesByAsin = $salesAndTraffic['salesByAsin']; // 销量

            $unitsOrdered = (int) $salesByAsin['unitsOrdered'];
            if ($unitsOrdered === 0) {
                continue;
            }

            $asin_maps[$childAsin] = $unitsOrdered;
        }

        foreach ($asin_maps as $asin => $quantity_ordered) {
            $hash[$asin] = $quantity_ordered;
        }
        $hash->ttl(23 * 3600);

        return true;
    }

    /**
     * @throws \Exception
     */
    public function buildReportBody(string $report_type, array $marketplace_ids): CreateReportSpecification
    {
        return new CreateReportSpecification([
            'report_options' => [
                'dateGranularity' => 'DAY',
                'asinGranularity' => 'SKU',
            ],
            'report_type' => $report_type, // 报告类型
            'data_start_time' => $this->getReportStartDate(), // 报告数据开始时间
            'data_end_time' => $this->getReportEndDate(), // 报告数据结束时间
            'marketplace_ids' => $marketplace_ids, // 市场标识符列表
        ]);
    }

    /**
     * @throws \Exception
     */
    public function requestReport(array $marketplace_ids, callable $func): void
    {
        foreach ($this->date_list as $item) {
            $this->setReportStartDate($item['start_time']);
            $this->setReportEndDate($item['end_time']);

            foreach ($marketplace_ids as $marketplace_id) {
                is_callable($func) && $func($this, 'GET_SALES_AND_TRAFFIC_REPORT_CUSTOM', $this->buildReportBody($this->getReportType(), [$marketplace_id]), [$marketplace_id]);
            }
        }
    }

    public function getReportFileName(array $marketplace_ids, string $region, string $report_id = ''): string
    {
        return $this->getReportType() . '-' . $marketplace_ids[0] . '-' . $this->getReportStartDate()?->format('Ymd') . '-' . $this->getReportEndDate()?->format('Ymd');
    }

    /**
     * 处理报告.
     *
     * @throws \Exception
     */
    public function processReport(callable $func, array $marketplace_ids): void
    {
        if ($this->checkReportDate()) {
            throw new \InvalidArgumentException('Report Start/End Date Required,please check');
        }

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
