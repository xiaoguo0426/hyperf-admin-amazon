<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Command\Crontab\Amazon;

use AmazonPHP\SellingPartner\AccessToken;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use App\Model\AmazonOrderModel;
use App\Util\Amazon\Creator\OrderCreator;
use App\Util\Amazon\Engine\OrderEngine;
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
class RefreshPendingOrder extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('crontab:amazon:refresh-pending-order');
    }

    public function configure(): void
    {
        parent::configure();
        // 指令配置
        $this->addArgument('merchant_id', InputArgument::REQUIRED, '商户id')
            ->addArgument('merchant_store_id', InputArgument::REQUIRED, '店铺id')
            ->addOption('order_ids', null, InputOption::VALUE_OPTIONAL, 'order_ids集合', null)
            ->setDescription('Crontab Amazon Refresh Pending Order Command');
    }

    /**
     * @throws NotFoundException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \RedisException
     */
    public function handle(): void
    {
        $merchant_id = (int) $this->input->getArgument('merchant_id');
        $merchant_store_id = (int) $this->input->getArgument('merchant_store_id');

        AmazonApp::tok($merchant_id, $merchant_store_id, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) {
            $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);

            $orders = AmazonOrderModel::query()
                ->where('merchant_id', $merchant_id)
                ->where('merchant_store_id', $merchant_store_id)
                ->where('region', $region)
                ->select('amazon_order_id')
                ->where('order_status', 'Pending')
                ->get();
            if ($orders->isEmpty()) {
                $console->warning('没有符合条件的数据');
                return true;
            }

            $orders->chunk(50)->each(static function ($collections) use ($marketplace_ids, $amazonSDK, $sdk, $accessToken): void {
                $amazon_order_ids = [];
                foreach ($collections as $collection) {
                    $amazon_order_ids[] = $collection->amazon_order_id;
                }

                $created_after = null;
                $nextToken = null;
                $max_results_per_page = 100;

                $orderCreator = new OrderCreator();
                $orderCreator->setMarketplaceIds($marketplace_ids);
                $orderCreator->setMaxResultsPerPage($max_results_per_page);
                $orderCreator->setCreatedAfter($created_after);
                $orderCreator->setNextToken($nextToken);
                $orderCreator->setAmazonOrderIds($amazon_order_ids);

                make(OrderEngine::class, [$amazonSDK, $sdk, $accessToken])->launch($orderCreator);
            });

            return true;
        });
    }
}
