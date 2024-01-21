<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Command\Amazon\FulfillmentInbound;

use _PHPStan_3d4486d07\Symfony\Component\Console\Input\InputOption;
use AmazonPHP\SellingPartner\AccessToken;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use App\Util\Amazon\Creator\GetShipmentItemsCreator;
use App\Util\Amazon\Engine\GetShipmentItemsEngine;
use App\Util\AmazonApp;
use App\Util\AmazonSDK;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Di\Exception\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RedisException;
use Symfony\Component\Console\Input\InputArgument;
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
            ->addOption('shipment_ids', null, \Symfony\Component\Console\Input\InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'ASIN 列表(英文逗号分隔)', null)
            ->setDescription('Amazon Fulfillment Inbound Get Shipment Items Command');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundException
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     * @return void
     */
    public function handle(): void
    {
        $merchant_id = (int) $this->input->getArgument('merchant_id');
        $merchant_store_id = (int) $this->input->getArgument('merchant_store_id');
        $region = $this->input->getArgument('region');
        $shipment_ids = $this->input->getOption('shipment_ids');

        AmazonApp::tok2($merchant_id, $merchant_store_id, $region, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) use ($shipment_ids) {

            $query_type = 'SHIPMENT';

            $last_updated_after = null;
            $last_updated_before = null;

            $getShipmentItemsCreator = new GetShipmentItemsCreator();
            $getShipmentItemsCreator->setQueryType($query_type);
            $getShipmentItemsCreator->setMarketplaceId('');
            $getShipmentItemsCreator->setLastUpdatedAfter($last_updated_after);
            $getShipmentItemsCreator->setLastUpdatedBefore($last_updated_before);

            make(GetShipmentItemsEngine::class)->launch($amazonSDK, $sdk, $accessToken, $getShipmentItemsCreator);
            return true;
        });
    }
}
