<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Command\Fake;

use AmazonPHP\SellingPartner\Exception\InvalidArgumentException;
use AmazonPHP\SellingPartner\Marketplace;
use AmazonPHP\SellingPartner\Regions;
use App\Queue\AmazonReportDocumentActionQueue;
use App\Queue\Data\AmazonReportDocumentActionData;
use App\Util\ConsoleLog;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Context\ApplicationContext;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RedisException;
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
            ->addArgument('report_document_id', InputArgument::OPTIONAL, '报告ID')
            ->setDescription('Fake Amazon Report Action Document Queue');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     * @return void
     */
    public function handle(): void
    {
        $merchant_id = (int) $this->input->getArgument('merchant_id');
        $merchant_store_id = (int) $this->input->getArgument('merchant_store_id');
        $report_type = (string) $this->input->getArgument('report_type');
        $report_document_id = (string) $this->input->getArgument('report_document_id');

        $console = ApplicationContext::getContainer()->get(ConsoleLog::class);

        $amazonReportDocumentActionQueue = new AmazonReportDocumentActionQueue();

        $report_file_dir = sprintf('%s%s/%s/%s-%s/', config('amazon.report_template_path'), 'scheduled', $report_type, $merchant_id, $merchant_store_id);


        $items = scandir($report_file_dir);

        $region_list = [
            Regions::EUROPE,
            Regions::NORTH_AMERICA,
            Regions::FAR_EAST,
        ];

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $report_document_id_raw = pathinfo($item, PATHINFO_FILENAME);
            //如果指定了id，则优先过滤
            if ($report_document_id && ! str_contains($report_document_id_raw, $report_document_id)) {
                continue;
            }

            if ($report_type === 'GET_DATE_RANGE_FINANCIAL_TRANSACTION_DATA') {
                $pos = strrpos($report_document_id_raw, '-');
                //解析marketplace_id
                $marketplace_id = substr($report_document_id_raw, $pos + 1);

                try {
                    $region = Marketplace::fromId($marketplace_id)->region();
                } catch (InvalidArgumentException $e) {
                    $console->error(sprintf('report_document_id:%s 解析Region失败. marketplace_id:%s', $report_document_id_raw, $marketplace_id));
                    continue;
                }

                $report_document_id_new = substr($report_document_id_raw, 0, $pos);

                $amazonReportDocumentActionData = new AmazonReportDocumentActionData();
                $amazonReportDocumentActionData->setMerchantId($merchant_id);
                $amazonReportDocumentActionData->setMerchantStoreId($merchant_store_id);
                $amazonReportDocumentActionData->setRegion($region);
                $amazonReportDocumentActionData->setMarketplaceIds([$marketplace_id]);
                $amazonReportDocumentActionData->setReportType($report_type);
                $amazonReportDocumentActionData->setReportDocumentId($report_document_id_new);

                $amazonReportDocumentActionQueue->push($amazonReportDocumentActionData);

            } else if ($report_type === 'GET_V2_SETTLEMENT_REPORT_DATA_FLAT_FILE_V2') {

                foreach ($region_list as $_region) {
                    if (! str_contains($report_document_id_raw, $_region)) {
                        continue;
                    }

                    $report_document_id_new = str_replace('-' . $_region, '', $report_document_id_raw);
                    $amazonReportDocumentActionData = new AmazonReportDocumentActionData();
                    $amazonReportDocumentActionData->setMerchantId($merchant_id);
                    $amazonReportDocumentActionData->setMerchantStoreId($merchant_store_id);
                    $amazonReportDocumentActionData->setRegion($_region);
                    $amazonReportDocumentActionData->setMarketplaceIds([]);
                    $amazonReportDocumentActionData->setReportType($report_type);
                    $amazonReportDocumentActionData->setReportDocumentId($report_document_id_new);

                    $amazonReportDocumentActionQueue->push($amazonReportDocumentActionData);
                }

            }

        }
    }
}
