<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Command\Amazon\FulfillmentInbound;

use AmazonPHP\SellingPartner\AccessToken;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use App\Util\Amazon\Creator\GetShipmentItemsCreator;
use App\Util\Amazon\Engine\GetShipmentItemsEngine;
use App\Util\AmazonApp;
use App\Util\AmazonSDK;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Di\Exception\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use function Hyperf\Support\make;

#[Command]
class GetShipmentItems extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('amazon:fulfillment-inbound:get-shipment-items');
    }

    public function configure(): void
    {
        parent::configure();
        $this->addArgument('merchant_id', InputArgument::REQUIRED, '商户id')
            ->addArgument('merchant_store_id', InputArgument::REQUIRED, '店铺id')
            ->addArgument('region', InputArgument::REQUIRED, '地区')
            ->addArgument('marketplace_id', InputArgument::REQUIRED, '市场id')
            ->addOption('last_updated_after', null, InputOption::VALUE_OPTIONAL, '用于选择在指定时间之后（或在）最后更新的入站货件的日期', null)
            ->addOption('last_updated_before', null, InputOption::VALUE_OPTIONAL, '用于选择在指定时间之前（或在）最后更新的入站货件的日期', null)
            ->setDescription('Amazon Fulfillment Inbound Get Shipment Items Command');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundException
     * @throws NotFoundExceptionInterface
     * @throws \RedisException
     */
    public function handle(): void
    {
        $merchant_id = (int) $this->input->getArgument('merchant_id');
        $merchant_store_id = (int) $this->input->getArgument('merchant_store_id');
        $region = $this->input->getArgument('region');
        $marketplace_id = $this->input->getArgument('marketplace_id');
        $last_updated_after = $this->input->getOption('last_updated_after');
        $last_updated_before = $this->input->getOption('last_updated_before');
        if (is_null($last_updated_after) && is_null($last_updated_before)) {
            $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
            $console->error('last_updated_after与last_updated_before 必须指定其中一个参数');
            return;
        }

        AmazonApp::tok2($merchant_id, $merchant_store_id, $region, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) use ($marketplace_id, $last_updated_after, $last_updated_before) {
            $query_type = 'DATE_RANGE';

            $getShipmentItemsCreator = new GetShipmentItemsCreator();
            $getShipmentItemsCreator->setQueryType($query_type);
            $getShipmentItemsCreator->setMarketplaceId($marketplace_id);
            $getShipmentItemsCreator->setLastUpdatedAfter($last_updated_after);
            $getShipmentItemsCreator->setLastUpdatedBefore($last_updated_before);

            make(GetShipmentItemsEngine::class)->launch($amazonSDK, $sdk, $accessToken, $getShipmentItemsCreator);
            return true;
        });
    }
}
