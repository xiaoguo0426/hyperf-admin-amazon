<?php

namespace App\Command\Crontab\Amazon;

use _PHPStan_3d4486d07\Symfony\Component\Console\Input\InputOption;
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
use Symfony\Component\Console\Input\InputArgument;
use function Hyperf\Support\make;

#[Command]
class GetShipmentItemsByShipmentId extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('crontab:amazon:get-shipment-items-by-shipment-id');
    }

    public function configure(): void
    {
        parent::configure();
        $this->setDescription('Crontab Amazon Fulfillment Inbound Get Shipment Items By Shipment Id Command');
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
            //TODO
        });
    }
}