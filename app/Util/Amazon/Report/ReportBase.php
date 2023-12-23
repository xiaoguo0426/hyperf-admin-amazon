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
use App\Util\Log\AmazonReportLog;
use App\Util\RedisHash\AmazonReportMarkCanceledHash;
use Carbon\Carbon;
use DateTimeInterface;
use Hyperf\Context\ApplicationContext;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

use function Hyperf\Config\config;
use function Hyperf\Support\make;

abstract class ReportBase implements ReportInterface
{
    protected string $dir;

    private string $report_type;

    private int $merchant_id;

    private int $merchant_store_id;

    private ?Carbon $report_start_date;

    private ?Carbon $report_end_date;

    private array $header_map;

    public function __construct(string $report_type, int $merchant_id, int $merchant_store_id)
    {
        $this->report_type = $report_type;

        $this->merchant_id = $merchant_id;
        $this->merchant_store_id = $merchant_store_id;

        $this->report_start_date = null;
        $this->report_end_date = null;

        $header_map = config('amazon_report_headers.' . $this->report_type);
        if (is_null($header_map)) {
            throw new \RuntimeException(sprintf('请在config/amazon_report_headers.php文件中配置该报告类型%s表头映射关系', $this->report_type));
        }

        $this->header_map = $header_map;
    }

    /**
     * 处理报告内容.
     */
    abstract public function run(string $report_id, string $file): bool;

    /**
     * 构造报告请求报告参数(如果某些报告有特定参数，需要重写该方法).
     */
    public function buildReportBody(string $report_type, array $marketplace_ids): CreateReportSpecification
    {
        return new CreateReportSpecification([
            'report_options' => null,
            'report_type' => $report_type, // 报告类型
            'data_start_time' => $this->getReportStartDate(), // 报告数据开始时间
            'data_end_time' => $this->getReportEndDate(), // 报告数据结束时间
            'marketplace_ids' => $marketplace_ids, // 市场标识符列表
        ]);
    }

    /**
     * 请求报告(如果特定报告有时间分组请求，需要重写该方法，参考SalesAndTrafficReportCustom.php报告).
     */
    public function requestReport(array $marketplace_ids, callable $func): void
    {
        is_callable($func) && $func($this, $this->report_type, $this->buildReportBody($this->report_type, $marketplace_ids), $marketplace_ids);
    }

    /**
     * 报告名称(如果特定报告有).
     */
    public function getReportFileName(array $marketplace_ids): string
    {
        return $this->report_type;
    }

    /**
     * 获得报告文件完整路径.
     */
    public function getReportFilePath(array $marketplace_ids): string
    {
        return $this->dir . $this->getReportFileName($marketplace_ids) . $this->getFileExt();
    }

    /**
     * 处理报告.
     */
    public function processReport(callable $func, array $marketplace_ids): void
    {
        if ($this->checkReportDate()) {
            throw new \InvalidArgumentException('Report Start/End Date Required,please check');
        }
        is_callable($func) && $func($this, $marketplace_ids);
    }

    public function setReportType(string $report_type): void
    {
        $this->report_type = $report_type;
    }

    public function getReportType(): string
    {
        return $this->report_type;
    }

    public function getMerchantId(): int
    {
        return $this->merchant_id;
    }

    public function getMerchantStoreId(): int
    {
        return $this->merchant_store_id;
    }

    public function getHeaderMap(): array
    {
        return $this->header_map;
    }

    /**
     * @param ?string $date
     * @throws \Exception
     */
    public function setReportStartDate(?string $date): void
    {
        $this->report_start_date = $date ? new Carbon($date, 'UTC') : null;
    }

    public function getReportStartDate(): null|Carbon
    {
        return $this->report_start_date;
    }

    /**
     * @param ?string $date
     * @throws \Exception
     */
    public function setReportEndDate(?string $date): void
    {
        $this->report_end_date = $date ? new Carbon($date, 'UTC') : null;
    }

    public function getReportEndDate(): null|Carbon
    {
        return $this->report_end_date;
    }

    /**
     * 报告是否需要指定开始时间与结束时间.
     */
    public function reportDateRequired(): bool
    {
        return false;
    }

    public function checkReportDate(): bool
    {
        if ($this->reportDateRequired()) {
            if (is_null($this->report_start_date) || is_null($this->report_end_date)) {
                return false;
            }
        }
        return true;
    }

    public function checkDir(): bool
    {
        $date = (new Carbon($this->getReportStartDate() ? $this->getReportStartDate()->format('Ymd') : '-1 day'))->setTimezone('UTC');
        // 检测report_type是哪个
        $category = $this->checkReportTypeCategory($this->report_type);

        $dir = sprintf('%s%s/%s/%s/%s-%s/', config('amazon.report_template_path'), $category, $date->format('Y-m'), $date->format('d'), $this->merchant_id, $this->merchant_store_id);
        $this->dir = $dir;

        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            try {
                ApplicationContext::getContainer()->get(AmazonReportLog::class)->error(sprintf('Get Directory "%s" was not created', $dir));
            } catch (ContainerExceptionInterface|NotFoundExceptionInterface $e) {
            }
        }

        return true;
    }

    public function getDir(): string
    {
        return $this->dir;
    }

    /**
     * 检查report_type属于哪个类型  requested|scheduled.
     */
    public function checkReportTypeCategory(string $report_type): string
    {
        $all = config('amazon_reports');
        foreach ($all as $type => $report_list) {
            foreach ($report_list as $report_type_raw) {
                if ($report_type_raw === $report_type) {
                    return $type;
                }
            }
        }
        throw new \InvalidArgumentException('Invalid Report Type,please check');
    }

    /**
     * 检查报告文件是否存在.
     */
    public function checkReportFile(array $marketplace_ids): bool
    {
        return file_exists($this->getReportFilePath($marketplace_ids));
    }

    public function getFileExt(): string
    {
        return '.txt';
    }

    /**
     * 检查Report内容
     * @param string $file_path
     * @return bool
     */
    public function checkReportContent(string $file_path): bool
    {
        return true;
    }

    /**
     * 标记报告删除
     */
    public function markCanceled(string $report_type, array $marketplace_ids, ?DateTimeInterface $dataStartTime, ?DateTimeInterface $dataEndTime): bool
    {
        //被取消或被终止的报告需要被标记，今日内不再请求
        /**
         * @var AmazonReportMarkCanceledHash $amazonReportMarkCanceled
         */
        $amazonReportMarkCanceled = make(AmazonReportMarkCanceledHash::class,[$this->merchant_id, $this->merchant_store_id]);
        return $amazonReportMarkCanceled->mark($report_type, $marketplace_ids, $dataStartTime, $dataEndTime);
    }

    /**
     * 检查报告是否被标记删除
     */
    public function checkMarkCanceled(array $marketplace_ids): bool
    {
        $amazonReportMarkCanceled = make(AmazonReportMarkCanceledHash::class,[$this->merchant_id, $this->merchant_store_id]);
        return $amazonReportMarkCanceled->check($this->report_type, $marketplace_ids, $this->getReportStartDate(), $this->getReportEndDate());
    }
}
