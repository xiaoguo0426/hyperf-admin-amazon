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
use App\Util\Amazon\Creator\GetShipmentsCreator;
use App\Util\Amazon\Engine\GetShipmentsEngine;
use App\Util\AmazonApp;
use App\Util\AmazonSDK;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Di\Exception\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use function Hyperf\Support\make;

#[Command]
class GetShipments extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('amazon:fulfillment-inbound:get-shipments');
    }

    public function configure(): void
    {
        parent::configure();
        $this->addArgument('merchant_id', InputArgument::REQUIRED, '商户id')
            ->addArgument('merchant_store_id', InputArgument::REQUIRED, '店铺id')
            ->addArgument('region', InputArgument::REQUIRED, '地区')
            ->addOption('shipment_ids', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'ASIN 列表(英文逗号分隔)', null)
            ->addOption('last_updated_after', null, InputOption::VALUE_OPTIONAL, '指定时间之后', null)
            ->addOption('last_updated_before', null, InputOption::VALUE_OPTIONAL, '指定时间之前', null)
            ->setDescription('Amazon Fulfillment Inbound Get Shipments Command');
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
        $shipment_ids = $this->input->getOption('shipment_ids');
        $last_updated_after = $this->input->getOption('last_updated_after');
        $last_updated_before = $this->input->getOption('last_updated_before');

        AmazonApp::tok2($merchant_id, $merchant_store_id, $region, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) use ($shipment_ids, $last_updated_after, $last_updated_before) {
            $shipment_status_list = [
                'WORKING',
                'READY_TO_SHIP',
                'SHIPPED',
                'RECEIVING',
                'CANCELLED',
                'DELETED',
                'CLOSED',
                'ERROR',
                'IN_TRANSIT',
                'DELIVERED',
                'CHECKED_IN',
            ];

            if (count($shipment_ids) > 0) {
                // 如果指定shipment_id，则不能指定marketplace_id,last_updated_after,last_updated_before
                $query_type = 'SHIPMENT';
                // TODO 优化 检查shipment_ids是否存在
                $getShipmentsCreator = new GetShipmentsCreator();
                $getShipmentsCreator->setQueryType($query_type);
                $getShipmentsCreator->setMarketplaceId(''); // 如果指定shipment_id，则不能指定marketplace_id
                $getShipmentsCreator->setShipmentStatusList($shipment_status_list);
                $getShipmentsCreator->setShipmentIdList($shipment_ids);
                $getShipmentsCreator->setLastUpdatedAfter(null);
                $getShipmentsCreator->setLastUpdatedBefore(null);

                make(GetShipmentsEngine::class)->launch($amazonSDK, $sdk, $accessToken, $getShipmentsCreator);
            } elseif (! is_null($last_updated_after) || ! is_null($last_updated_before)) {
                // 如果指定last_updated_after,last_updated_before,则不能指定marketplace_id,shipment_id
                $query_type = 'DATE_RANGE';
                $getShipmentsCreator = new GetShipmentsCreator();
                $getShipmentsCreator->setQueryType($query_type);
                $getShipmentsCreator->setMarketplaceId('');
                $getShipmentsCreator->setShipmentStatusList($shipment_status_list);
                $getShipmentsCreator->setShipmentIdList([]);
                $getShipmentsCreator->setLastUpdatedAfter($last_updated_after);
                $getShipmentsCreator->setLastUpdatedBefore($last_updated_before);

                make(GetShipmentsEngine::class)->launch($amazonSDK, $sdk, $accessToken, $getShipmentsCreator);
            } else {
                $query_type = 'SHIPMENT';
                foreach ($marketplace_ids as $marketplace_id) {
                    $getShipmentsCreator = new GetShipmentsCreator();
                    $getShipmentsCreator->setQueryType($query_type);
                    $getShipmentsCreator->setMarketplaceId($marketplace_id);
                    $getShipmentsCreator->setShipmentStatusList($shipment_status_list);
                    $getShipmentsCreator->setShipmentIdList([]);
                    $getShipmentsCreator->setLastUpdatedAfter(null);
                    $getShipmentsCreator->setLastUpdatedBefore(null);

                    make(GetShipmentsEngine::class)->launch($amazonSDK, $sdk, $accessToken, $getShipmentsCreator);
                }
            }
            return true;
        });
    }
}
