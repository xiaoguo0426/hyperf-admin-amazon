<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Command\Amazon\Order;

use AmazonPHP\SellingPartner\AccessToken;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use App\Util\Amazon\Creator\OrderItemCreator;
use App\Util\Amazon\Engine\OrderItemEngine;
use App\Util\AmazonApp;
use App\Util\AmazonSDK;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Input\InputArgument;
use function Hyperf\Support\make;

#[Command]
class GetOrderItems extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('amazon:order:get-order-items');
    }

    public function configure(): void
    {
        parent::configure();
        // 指令配置
        $this->addArgument('merchant_id', InputArgument::REQUIRED, '商户id')
            ->addArgument('merchant_store_id', InputArgument::REQUIRED, '店铺id')
            ->addArgument('region', InputArgument::REQUIRED, '地区')
            ->addArgument('order_ids', InputArgument::REQUIRED, 'order_id集合')
            ->setDescription('Amazon Order API Get Order Items');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \RedisException
     */
    public function handle(): void
    {
        $merchant_id = (int) $this->input->getArgument('merchant_id');
        $merchant_store_id = (int) $this->input->getArgument('merchant_store_id');
        $region = $this->input->getArgument('region');
        $amazon_order_ids = $this->input->getArgument('order_ids');
        $amazon_order_ids = explode(',', $amazon_order_ids);

        AmazonApp::tok2($merchant_id, $merchant_store_id, $region, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) use ($amazon_order_ids) {

            $orderItemCreator = new OrderItemCreator();
            $orderItemCreator->setAmazonOrderIds($amazon_order_ids);

            make(OrderItemEngine::class)->launch($amazonSDK, $sdk, $accessToken, $orderItemCreator);

            return true;
        });
    }
}
