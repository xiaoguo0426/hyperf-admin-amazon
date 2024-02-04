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
use App\Model\AmazonReportCouponPerformanceAsinsModel;
use App\Model\AmazonReportCouponPerformanceModel;
use App\Util\Amazon\Report\Runner\ReportRunnerInterface;
use App\Util\Amazon\Report\Runner\RequestedReportRunner;
use App\Util\ConsoleLog;
use App\Util\Log\AmazonReportLog;
use Carbon\Carbon;
use Hyperf\Context\ApplicationContext;
use Hyperf\Database\Model\ModelNotFoundException;
use JsonException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class CouponPerformanceReport extends ReportBase
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
     * @return bool
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
        } catch (JsonException $jsonException) {
            $log = sprintf('Action %s 解析错误 merchant_id: %s merchant_store_id: %s', $this->getReportType(), $merchant_id, $merchant_store_id);
            $console->error($log);
            $logger->error($log);
            return true;
        }

        $coupons = $json['coupons'];
        if (empty($coupons)) {
            return true;
        }
        foreach ($coupons as $coupon) {

            $coupon_id = $coupon['couponId'];
            $amazon_merchant_id = $coupon['merchantId'];
            $marketplace_id = $coupon['marketplaceId'];
            $currency_code = $coupon['currencyCode'];
            $name = $coupon['name'];
            $website_message = $coupon['websiteMessage'];
            $start_date_time = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $coupon['startDateTime'])->format('Y-m-d H:i:s');
            $end_date_time = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $coupon['endDateTime'])->format('Y-m-d H:i:s');
            $discount_type = $coupon['discountType'] ?? '';
            $discount_amount = $coupon['discountAmount'] ?? 0.00;
            $total_discount = $coupon['totalDiscount'] ?? 0.00;
            $clips = $coupon['clips'];
            $redemptions = $coupon['redemptions'];
            $budget = $coupon['budget'] ?? '';
            $budget_spent = $coupon['budgetSpent'];
            $budget_remaining = $coupon['budgetRemaining'] ?? '';
            $budget_percentage_used = $coupon['budgetPercentageUsed'] ?? '';
            $budget_sales = $coupon['sales'] ?? '';

            try {
                $model = AmazonReportCouponPerformanceModel::query()
                    ->where('merchant_id', $merchant_id)
                    ->where('merchant_store_id', $merchant_store_id)
                    ->where('coupon_id', $coupon_id)
                    ->where('amazon_merchant_id', $amazon_merchant_id)
                    ->where('marketplace_id', $marketplace_id)
                    ->firstOrFail();
            } catch (ModelNotFoundException $modelNotFoundException) {
                $model = new AmazonReportCouponPerformanceModel();
                $model->merchant_id = $merchant_id;
                $model->merchant_store_id = $merchant_store_id;
                $model->coupon_id = $coupon_id;
                $model->amazon_merchant_id = $amazon_merchant_id;
                $model->marketplace_id = $marketplace_id;
            }

            $model->currency_code = $currency_code;
            $model->name = $name;
            $model->website_message = $website_message;
            $model->start_date_time = $start_date_time;
            $model->end_date_time = $end_date_time;
            $model->discount_type = $discount_type;
            $model->discount_amount = $discount_amount;
            $model->total_discount = $total_discount;
            $model->clips = $clips;
            $model->redemptions = $redemptions;
            $model->budget = $budget;
            $model->budget_spent = $budget_spent;
            $model->budget_remaining = $budget_remaining;
            $model->budget_percentage_used = $budget_percentage_used;
            $model->budget_sales = $budget_sales;

            $model->save();

            $coupon_performance_id = $model->id;

            $asins = $coupon['asins'];
            foreach ($asins as $asin_item) {
                try {
                    $amazonReportCouponPerformanceAsinModel = AmazonReportCouponPerformanceAsinsModel::query()
                        ->where('merchant_id', $merchant_id)
                        ->where('merchant_store_id', $merchant_store_id)
                        ->where('coupon_performance_id', $coupon_performance_id)
                        ->firstOrFail();
                } catch (ModelNotFoundException $modelNotFoundException) {
                    $amazonReportCouponPerformanceAsinModel = new AmazonReportCouponPerformanceAsinsModel();
                    $amazonReportCouponPerformanceAsinModel->merchant_id = $merchant_id;
                    $amazonReportCouponPerformanceAsinModel->merchant_store_id = $merchant_store_id;
                    $amazonReportCouponPerformanceAsinModel->coupon_performance_id = $coupon_performance_id;
                }
                $amazonReportCouponPerformanceAsinModel->asin = $asin_item['asin'];
                $amazonReportCouponPerformanceAsinModel->seller_sku = '';//TODO
                $amazonReportCouponPerformanceAsinModel->save();
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
                'campaignStartDateFrom' => $this->getReportStartDate()?->format('Y-m-d\TH:i:s\Z'),
                'campaignStartDateTo' => $this->getReportEndDate()?->format('Y-m-d\TH:i:s\Z'),
            ],
            'report_type' => $report_type,//报告类型
            'marketplace_ids' => $marketplace_ids,//市场标识符列表
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
