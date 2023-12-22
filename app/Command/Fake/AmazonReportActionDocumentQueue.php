<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Command\Fake;

use App\Queue\AmazonReportDocumentActionQueue;
use App\Queue\Data\AmazonReportDocumentActionData;
use App\Util\ConsoleLog;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Context\ApplicationContext;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;
use function Hyperf\Config\config;

#[Command]
class AmazonReportActionDocumentQueue extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('fake:amazon-report-action-document-queue');
    }

    public function configure(): void
    {
        parent::configure();
        // 指令配置
        $this->addArgument('merchant_id', InputArgument::REQUIRED, '商户id')
            ->addArgument('merchant_store_id', InputArgument::REQUIRED, '店铺id')
            ->addArgument('report_type', InputArgument::REQUIRED, '报告类型')
            ->addArgument('report_id', InputArgument::OPTIONAL, '报告ID')
            ->setDescription('Fake Amazon Report Action Document Queue');
    }

    public function handle(): void
    {
        $merchant_id = (int) $this->input->getArgument('merchant_id');
        $merchant_store_id = (int) $this->input->getArgument('merchant_store_id');
        $report_type = (string) $this->input->getArgument('report_type');
        $report_id = (string) $this->input->getArgument('report_id');

        $console = ApplicationContext::getContainer()->get(ConsoleLog::class);

        $amazonReportDocumentActionQueue = new AmazonReportDocumentActionQueue();

        $report_file_dir = sprintf('%s%s/%s/%s-%s/', config('amazon.report_template_path'), 'scheduled', $report_type, $merchant_id, $merchant_store_id);
        if ($report_id) {
            $file_path = sprintf('%s%s.txt', $report_file_dir, $report_id);
            if (! file_exists($file_path)) {
                $console->error(sprintf('%s 文件不存在', $file_path));
                return;
            }

            $amazonReportDocumentActionData = new AmazonReportDocumentActionData();
            $amazonReportDocumentActionData->setMerchantId($merchant_id);
            $amazonReportDocumentActionData->setMerchantStoreId($merchant_store_id);
            $amazonReportDocumentActionData->setReportType($report_type);
            $amazonReportDocumentActionData->setReportDocumentId($report_id);

            $amazonReportDocumentActionQueue->push($amazonReportDocumentActionData);
        } else {
            $items = scandir($report_file_dir);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                $report_document_id = pathinfo($item, PATHINFO_FILENAME);

                $amazonReportDocumentActionData = new AmazonReportDocumentActionData();
                $amazonReportDocumentActionData->setMerchantId($merchant_id);
                $amazonReportDocumentActionData->setMerchantStoreId($merchant_store_id);
                $amazonReportDocumentActionData->setReportType($report_type);
                $amazonReportDocumentActionData->setReportDocumentId($report_document_id);

                $amazonReportDocumentActionQueue->push($amazonReportDocumentActionData);
            }
        }
    }
}
