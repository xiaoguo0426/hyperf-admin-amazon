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
use App\Model\AmazonOrderModel;
use App\Util\Amazon\Creator\OrderCreator;
use App\Util\Amazon\Engine\OrderEngine;
use App\Util\AmazonApp;
use App\Util\AmazonSDK;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use function Hyperf\Support\make;
use function Sentry\continueTrace;

#[Command]
class GetOrders extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('amazon:order:get-orders');
    }

    public function configure(): void
    {
        parent::configure();
        // 指令配置
        $this->addArgument('merchant_id', InputArgument::REQUIRED, '商户id')
            ->addArgument('merchant_store_id', InputArgument::REQUIRED, '店铺id')
            ->addOption('region', null, InputOption::VALUE_OPTIONAL, '地区', null)
            ->addOption('order_ids', null, InputOption::VALUE_OPTIONAL, 'order_ids集合', null)
            ->setDescription('Amazon Order API Get Orders');
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \RedisException
     * @return void
     */
    public function handle(): void
    {
        $merchant_id = (int) $this->input->getArgument('merchant_id');
        $merchant_store_id = (int) $this->input->getArgument('merchant_store_id');
        $real_region = $this->input->getOption('region');
        $amazon_order_ids = $this->input->getOption('order_ids');

        $that = $this;

        AmazonApp::tok($merchant_id, $merchant_store_id, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) use ($real_region, $amazon_order_ids) {
            $created_after = null;
            if (! is_null($real_region) && ! is_null($amazon_order_ids)) {
                if ($real_region !== $region) {
                    return true;
                }
                $amazon_order_ids = explode(',', $amazon_order_ids);
            } else {
                $last_create_date = AmazonOrderModel::query()
                    ->where('merchant_id', $merchant_id)
                    ->where('merchant_store_id', $merchant_store_id)
                    ->where('region', $region)
                    ->orderBy('purchase_date', 'DESC')
                    ->value('purchase_date');
                if (is_null($last_create_date)) {
                    $created_after = (new \DateTime('-1 year', new \DateTimeZone('UTC')))->format('Y-01-01\T00:00:00\Z');
                } else {
                    $created_after = (new \DateTime($last_create_date, new \DateTimeZone('UTC')))->sub(new \DateInterval('P1D'))->format('Y-m-d\T00:00:00\Z');
                }
            }

            $nextToken = null;
            $max_results_per_page = 100;

            $orderCreator = new OrderCreator();
            $orderCreator->setMarketplaceIds($marketplace_ids);
            $orderCreator->setMaxResultsPerPage($max_results_per_page);
            $orderCreator->setCreatedAfter($created_after);
            $orderCreator->setNextToken($nextToken);
            $orderCreator->setAmazonOrderIds($amazon_order_ids);

            make(OrderEngine::class)->launch($amazonSDK, $sdk, $accessToken, $orderCreator);

            return true;
        });
    }
}
