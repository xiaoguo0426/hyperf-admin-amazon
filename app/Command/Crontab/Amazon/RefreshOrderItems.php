<?php

namespace App\Command\Crontab\Amazon;

use AmazonPHP\SellingPartner\AccessToken;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use App\Queue\AmazonOrderItemQueue;
use App\Queue\Data\AmazonOrderItemData;
use App\Util\AmazonApp;
use App\Util\AmazonSDK;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Context\ApplicationContext;
use Hyperf\DB\DB;
use Hyperf\Di\Exception\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RedisException;
use Symfony\Component\Console\Input\InputArgument;

#[Command]
class RefreshOrderItems extends HyperfCommand
{

    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('crontab:amazon:refresh-order-items');
    }

    public function configure(): void
    {
        parent::configure();
        // 指令配置
        $this->addArgument('merchant_id', InputArgument::REQUIRED, '商户id')
            ->addArgument('merchant_store_id', InputArgument::REQUIRED, '店铺id')
            ->setDescription('Crontab Amazon Refresh Order Items Command');
    }

    /**
     * @throws NotFoundException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     * @return void
     */
    public function handle(): void
    {
        $merchant_id = (int) $this->input->getArgument('merchant_id');
        $merchant_store_id = (int) $this->input->getArgument('merchant_store_id');

        //检查amazon_order_items缺少的数据,并构造队列数据
        AmazonApp::tok($merchant_id, $merchant_store_id, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) {

            $orderItemQueue = ApplicationContext::getContainer()->get(AmazonOrderItemQueue::class);

            $amazon_list = DB::query("SELECT o.amazon_order_id FROM amazon_order o LEFT JOIN amazon_order_items i ON o.merchant_id = i.merchant_id AND o.merchant_store_id = i.merchant_store_id AND o.amazon_order_id = i.order_id  WHERE o.merchant_id={$merchant_id} and o.merchant_store_id= {$merchant_store_id} and o.region = '{$region}' AND i.order_id IS NULL;");
            if ($amazon_list) {
                $amazon_order_ids = array_column($amazon_list, 'amazon_order_id');

                $chunks = array_chunk($amazon_order_ids, 10);
                $amazonOrderData = new AmazonOrderItemData();
                foreach ($chunks as $order_id_list) {

                    $amazonOrderData->setMerchantId($merchant_id);
                    $amazonOrderData->setMerchantStoreId($merchant_store_id);
                    $amazonOrderData->setRegion($region);
                    $amazonOrderData->setOrderId($order_id_list);
                    $orderItemQueue->push($amazonOrderData);

                }

            }

        });
    }

}