<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Command\Fake;

use AmazonPHP\SellingPartner\AccessToken;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use App\Util\AmazonSDK;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\DB\DB;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;

#[Command]
class AmazonApp extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('fake:amazon-app');
    }

    public function configure(): void
    {
        parent::configure();
        // 指令配置
        $this->addArgument('merchant_id', InputArgument::REQUIRED, '商户id')
            ->addArgument('merchant_store_id', InputArgument::REQUIRED, '店铺id')
            ->setDescription('Amazon App');
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \RedisException
     */
    public function handle(): void
    {
        $merchant_id = (int) $this->input->getArgument('merchant_id');
        $merchant_store_id = (int) $this->input->getArgument('merchant_store_id');

        //        \App\Util\AmazonApp::tick($merchant_id, $merchant_store_id, static function (AmazonAppModel $amazonAppCollection) {
        //            //            $multiLog = \Hyperf\Support\make(MultiLog::class);
        //            $multiLog = new MultiLog();
        //            $multiLog->register(di(StdoutLoggerInterface::class))->register(di(AmazonFbaInventoryLog::class));
        //            //            $multiLog->info('{a} 343242342423423 {b}', ['a' => 1, 'b' => 333]);
        //            $multiLog->info('自定义日志信息 {a}-{b}', ['a' => 1, 'b' => 333]);
        //            $multiLog->error('自定义日志信息 {a}-{b}', ['a' => 3333, 'b' => 4444]);
        //            $multiLog->alert('自定义日志信息 {a}-{b}', ['a' => 3333, 'b' => 4444]);
        //            $multiLog->warning('自定义日志信息 {a}-{b}', ['a' => 3333, 'b' => 4444]);
        //            $multiLog->notice('自定义日志信息');
        //            //            var_dump($amazonAppCollection->getRegionRefreshTokenConfigs());
        //            return true;
        //        });

//        \App\Util\AmazonApp::tok($merchant_id, $merchant_store_id, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) {
//            return true;
//        });

        $merchant_id = 1;
        $merchant_store_id = 1;
        $region = 'us-east-1';
        $amazon_order_id = '113-1610073-2158614';
        $seller_sku = 'DEY-6A20615-BLACK-US';
        $other = DB::query("SELECT amazon_order.order_status,amazon_order.marketplace_id,amazon_order.order_total_currency,amazon_order_items.item_price FROM amazon_order INNER JOIN amazon_order_items ON amazon_order.amazon_order_id = amazon_order_items.order_id AND amazon_order_items.item_price <> '' AND   amazon_order_items.item_price <> '[]' AND amazon_order.marketplace_id = (select marketplace_id FROM amazon_order WHERE merchant_id = {$merchant_id} and merchant_store_id = {$merchant_store_id} and region = '{$region}' and amazon_order_id = '{$amazon_order_id}') AND amazon_order_items.seller_sku = '{$seller_sku}' AND amazon_order_items.quantity_ordered = 1 ORDER BY amazon_order_items.id DESC LIMIT 1;");
        var_dump($other);
    }
}
