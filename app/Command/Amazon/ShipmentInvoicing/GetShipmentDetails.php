<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Command\Amazon\ShipmentInvoicing;

use AmazonPHP\SellingPartner\AccessToken;
use AmazonPHP\SellingPartner\Exception\ApiException;
use AmazonPHP\SellingPartner\Exception\InvalidArgumentException;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use App\Util\AmazonApp;
use App\Util\AmazonSDK;
use App\Util\Log\AmazonSellerGetMarketplaceParticipationLog;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\StdoutLoggerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Input\InputArgument;

class GetShipmentDetails extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('amazon:sellers:get-marketplace-participation');
    }

    public function configure(): void
    {
        parent::configure();
        // 指令配置
        $this->addArgument('merchant_id', InputArgument::REQUIRED, '商户id')
            ->addArgument('merchant_store_id', InputArgument::REQUIRED, '店铺id')
            ->setDescription('Amazon Shipment Invoicing GetShipmentDetails');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function handle(): void
    {
        $merchant_id = (int) $this->input->getArgument('merchant_id');
        $merchant_store_id = (int) $this->input->getArgument('merchant_store_id');

        AmazonApp::tok($merchant_id, $merchant_store_id, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) {
            $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
            $logger = ApplicationContext::getContainer()->get(AmazonSellerGetMarketplaceParticipationLog::class);

            $retry = 10;

            //            while (true) {
            //                try {
            //                    $response = $sdk->shipmentInvoicing()->getShipmentDetails($accessToken, $region);
            //                    $marketplaceParticipationList = $response->getPayload();
            //                    if (is_null($marketplaceParticipationList)) {
            //                        break;
            //                    }
            //
            //                    $errors = $response->getErrors();
            //                    if (! is_null($errors)) {
            //                        break;
            //                    }
            //
            //                    break;
            //                } catch (ApiException $e) {
            //                    --$retry;
            //                    if ($retry > 0) {
            //                        continue;
            //                    }
            //                    break;
            //                } catch (InvalidArgumentException $e) {
            //                    continue;
            //                }
            //            }

            return true;
        });
    }
}
