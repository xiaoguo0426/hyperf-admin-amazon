<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Command\Amazon\Finance;

use AmazonPHP\SellingPartner\AccessToken;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use App\Model\AmazonFinancialGroupModel;
use App\Util\Amazon\Creator\ListFinancialEventsByGroupIdCreator;
use App\Util\Amazon\Engine\ListFinancialEventsByGroupIdEngine;
use App\Util\AmazonApp;
use App\Util\AmazonSDK;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Database\Model\ModelNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RedisException;
use Symfony\Component\Console\Input\InputArgument;

use function Hyperf\Support\make;

#[Command]
class ListFinancialEventsByGroupId extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('amazon:finance:list-financial-events-by-group-id');
    }

    public function configure(): void
    {
        parent::configure();
        $this->addArgument('merchant_id', InputArgument::REQUIRED, '商户id')
            ->addArgument('merchant_store_id', InputArgument::REQUIRED, '店铺id')
            ->addArgument('region', InputArgument::REQUIRED, '地区')
            ->addArgument('financial_event_group_id', InputArgument::REQUIRED, '财务组id')
            ->setDescription('Amazon Finance List Financial Events By Group Id Command');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     */
    public function handle(): void
    {
        $merchant_id = (int) $this->input->getArgument('merchant_id');
        $merchant_store_id = (int) $this->input->getArgument('merchant_store_id');
        $region = (string) $this->input->getArgument('region');
        $financial_event_group_id = (string) $this->input->getArgument('financial_event_group_id');

        AmazonApp::tok2($merchant_id, $merchant_store_id, $region, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) use ($financial_event_group_id) {
            $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);

            try {
                // 检查group_id对应的region
                $amazonFinancialGroupCollection = AmazonFinancialGroupModel::query()
                    ->where('merchant_id', $merchant_id)
                    ->where('merchant_store_id', $merchant_store_id)
                    ->where('region', $region)
                    ->where('financial_event_group_id', $financial_event_group_id)
                    ->firstOrFail();
            } catch (ModelNotFoundException) {
                $console->error(sprintf('merchant_id:%s merchant_store_id:%s region:%s financial_event_group_id:%s 数据不存在，请检查.', $merchant_id, $merchant_store_id, $region, $financial_event_group_id));
                return true;
            }

            $creator = new ListFinancialEventsByGroupIdCreator();
            $creator->setGroupId($financial_event_group_id);
            $creator->setMaxResultsPerPage(100);
            // https://spapi.vip/zh/references/finances-api-reference.html
            make(ListFinancialEventsByGroupIdEngine::class)->launch($amazonSDK, $sdk, $accessToken, $creator);

            return true;
        });
    }
}
