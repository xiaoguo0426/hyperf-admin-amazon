<?php

namespace App\Command\Crontab\Amazon;

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
use RedisException;
use function Hyperf\Support\make;

#[Command]
class GetShipments extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('crontab:amazon:get-shipments');
    }

    public function configure(): void
    {
        parent::configure();
        $this->setDescription('Crontab Amazon Fulfillment Inbound Get Shipments Command');
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
        AmazonApp::each(static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) {
            $query_type = 'SHIPMENT';

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
            //            $last_updated_after = (new \DateTime('2023-01-01', new \DateTimeZone('UTC')));
            //            $last_updated_before = (new \DateTime('2023-09-01', new \DateTimeZone('UTC')));
            $last_updated_after = null;
            $last_updated_before = null;

            $getShipmentsCreator = new GetShipmentsCreator();
            $getShipmentsCreator->setQueryType($query_type);
            $getShipmentsCreator->setMarketplaceId('');
            $getShipmentsCreator->setShipmentStatusList($shipment_status_list);
            $getShipmentsCreator->setShipmentIdList(null);
            $getShipmentsCreator->setLastUpdatedAfter($last_updated_after);
            $getShipmentsCreator->setLastUpdatedBefore($last_updated_before);

            make(GetShipmentsEngine::class)->launch($amazonSDK, $sdk, $accessToken, $getShipmentsCreator);
        });
    }
}