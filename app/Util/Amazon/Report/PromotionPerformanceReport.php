<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Util\Amazon\Report;

use AmazonPHP\SellingPartner\Model\Reports\CreateReportSpecification;
use App\Model\AmazonReportPromotionPerformanceModel;
use App\Model\AmazonReportPromotionPerformanceProductsModel;
use App\Util\Amazon\Report\Runner\ReportRunnerInterface;
use App\Util\Amazon\Report\Runner\RequestedReportRunner;
use App\Util\ConsoleLog;
use App\Util\Log\AmazonReportLog;
use Carbon\Carbon;
use Hyperf\Context\ApplicationContext;
use Hyperf\Database\Model\ModelNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class PromotionPerformanceReport extends ReportBase
{
    /**
     * @throws \Exception
     */
    public function __construct(int $merchant_id, int $merchant_store_id, string $region, string $report_type)
    {
        parent::__construct($merchant_id, $merchant_store_id, $region, $report_type);

        $start_time = Carbon::now('UTC')->subDays(30)->format('Y-m-d 00:00:00');
        $end_time = Carbon::now('UTC')->format('Y-m-d 23:59:59');

        $this->setReportStartDate($start_time);
        $this->setReportEndDate($end_time);
    }

    /**
     * @param RequestedReportRunner $reportRunner
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function run(ReportRunnerInterface $reportRunner): bool
    {
        $logger = ApplicationContext::getContainer()->get(AmazonReportLog::class);
        $console = ApplicationContext::getContainer()->get(ConsoleLog::class);

        $merchant_id = $this->getMerchantId();
        $merchant_store_id = $this->getMerchantStoreId();

        $file = $reportRunner->getReportFilePath();
        $report_id = $reportRunner->getReportId();

        $content = file_get_contents($file);
        try {
            $json = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $jsonException) {
            $log = sprintf('Action %s 解析错误 merchant_id: %s merchant_store_id: %s', $this->getReportType(), $merchant_id, $merchant_store_id);
            $console->error($log);
            $logger->error($log);
            return true;
        }

        $promotions = $json['promotions'];
        if (count($promotions) === 0) {
            return true;
        }

        foreach ($promotions as $promotion) {
            $promotion_id = $promotion['promotionId'];
            $promotion_name = $promotion['promotionName'];
            $marketplace_id = $promotion['marketplaceId'];
            $amazon_merchant_id = $promotion['merchantId'];
            $type = $promotion['type'];
            $status = $promotion['status'];
            $glance_views = $promotion['glanceViews'];
            $units_sold = $promotion['unitsSold'];
            $revenue = $promotion['revenue'];
            $revenue_currency_code = $promotion['revenueCurrencyCode'];
            $start_date_time = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $promotion['startDateTime'])->format('Y-m-d H:i:s');
            $end_date_time = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $promotion['endDateTime'])->format('Y-m-d H:i:s');
            $created_date_time = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $promotion['createdDateTime'])->format('Y-m-d H:i:s');
            $last_updated_date_time = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $promotion['lastUpdatedDateTime'])->format('Y-m-d H:i:s');

            try {
                $model = AmazonReportPromotionPerformanceModel::query()
                    ->where('merchant_id', $merchant_id)
                    ->where('merchant_store_id', $merchant_store_id)
                    ->where('promotion_id', $promotion_id)
                    ->where('amazon_merchant_id', $amazon_merchant_id)
                    ->where('marketplace_id', $marketplace_id)
                    ->firstOrFail();
            } catch (ModelNotFoundException $modelNotFoundException) {
                $model = new AmazonReportPromotionPerformanceModel();
                $model->merchant_id = $merchant_id;
                $model->merchant_store_id = $merchant_store_id;
                $model->promotion_id = $promotion_id;
                $model->amazon_merchant_id = $amazon_merchant_id;
                $model->marketplace_id = $marketplace_id;
                $model->type = $type;
            }
            $model->promotion_name = $promotion_name;
            $model->status = $status;
            $model->glance_views = $glance_views;
            $model->units_sold = $units_sold;
            $model->revenue = $revenue;
            $model->revenue_currency_code = $revenue_currency_code;
            $model->start_date_time = $start_date_time;
            $model->end_date_time = $end_date_time;
            $model->created_date_time = $created_date_time;
            $model->last_updated_date_time = $last_updated_date_time;

            $model->save();

            $promotion_performance_id = $model->id;

            $include_products = $promotion['includedProducts'];
            foreach ($include_products as $include_product) {
                $asin = $include_product['asin'];
                $product_name = $include_product['productName'];
                $product_glance_views = $include_product['productGlanceViews'];
                $product_units_sold = $include_product['productUnitsSold'];
                $product_revenue = $include_product['productRevenue'];
                $product_revenue_currency_code = $include_product['productRevenueCurrencyCode'];

                try {
                    $model = AmazonReportPromotionPerformanceProductsModel::query()
                        ->where('merchant_id', $merchant_id)
                        ->where('merchant_store_id', $merchant_store_id)
                        ->where('promotion_performance_id', $promotion_performance_id)
                        ->where('asin', $asin)
                        ->firstOrFail();
                } catch (ModelNotFoundException $modelNotFoundException) {
                    $model = new AmazonReportPromotionPerformanceProductsModel();
                    $model->merchant_id = $merchant_id;
                    $model->merchant_store_id = $merchant_store_id;
                    $model->promotion_performance_id = $promotion_performance_id;
                    $model->asin = $asin;
                }

                $model->seller_sku = ''; // TODO
                $model->product_name = $product_name;
                $model->product_glance_views = $product_glance_views;
                $model->product_units_sold = $product_units_sold;
                $model->product_revenue = $product_revenue;
                $model->product_revenue_currency_code = $product_revenue_currency_code;

                $model->save();
            }
        }

        return true;
    }

    /**
     * @throws \Exception
     */
    public function buildReportBody(string $report_type, array $marketplace_ids): CreateReportSpecification
    {
        return new CreateReportSpecification([
            'report_options' => [
                'promotionStartDateFrom' => $this->getReportStartDate()?->format('Y-m-d\TH:i:s\Z'),
                'promotionStartDateTo' => $this->getReportEndDate()?->format('Y-m-d\TH:i:s\Z'),
            ],
            'report_type' => $report_type, // 报告类型
            'marketplace_ids' => $marketplace_ids, // 市场标识符列表
            'data_start_time' => $this->getReportStartDate(),
            'data_end_time' => $this->getReportEndDate(),
        ]);
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

    public function getReportFileName(array $marketplace_ids, string $region, string $report_id = ''): string
    {
        return $this->getReportType() . '-' . $marketplace_ids[0] . '-' . $this->getReportStartDate()?->format('Ymd') . '-' . $this->getReportEndDate()?->format('Ymd');
    }

    /**
     * 处理报告.
     */
    public function processReport(callable $func, array $marketplace_ids): void
    {
        if (! $this->checkReportDate()) {
            throw new \InvalidArgumentException('Report Start/End Date Required,please check');
        }

        foreach ($marketplace_ids as $marketplace_id) {
            is_callable($func) && $func($this, [$marketplace_id]);
        }
    }

    /**
     * 请求该报告需要设置 开始时间和结束时间.
     */
    public function reportDateRequired(): bool
    {
        return true;
    }
}
