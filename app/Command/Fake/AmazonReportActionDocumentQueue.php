<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Command\Fake;

use AmazonPHP\SellingPartner\AccessToken;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use App\Queue\AmazonReportDocumentActionQueue;
use App\Queue\Data\AmazonReportDocumentActionData;
use App\Util\AmazonSDK;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;

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
            ->setDescription('Fake Amazon Report Action Document Queue');
    }

    public function handle(): void
    {
        $merchant_id = (int) $this->input->getArgument('merchant_id');
        $merchant_store_id = (int) $this->input->getArgument('merchant_store_id');
        $report_type = (string) $this->input->getArgument('report_type');

        $report_file_dir = sprintf('%s%s/%s/%s-%s/', \Hyperf\Config\config('amazon.report_template_path'), 'scheduled', $report_type, $merchant_id, $merchant_store_id);

        $amazonReportDocumentActionQueue = new AmazonReportDocumentActionQueue();

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
//
//        $amazonReportDocumentActionData = new AmazonReportDocumentActionData();
//        $amazonReportDocumentActionData->setMerchantId($merchant_id);
//        $amazonReportDocumentActionData->setMerchantStoreId($merchant_store_id);
//        $amazonReportDocumentActionData->setReportType($report_type);
//        $amazonReportDocumentActionData->setReportDocumentId('amzn1.spdoc.1.4.na.2eb15a3c-400f-4062-aab6-fb0b48c3ae1c.T3EEZT6NLPDADT.1202');
//
//        $amazonReportDocumentActionQueue->push($amazonReportDocumentActionData);

    }
}
