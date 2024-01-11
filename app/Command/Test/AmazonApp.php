<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Command\Test;

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
        parent::__construct('test:amazon-app');
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \RedisException
     */
    public function handle(): void
    {
        \App\Util\AmazonApp::each(static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) {
//            var_dump($merchant_id);
//            var_dump($merchant_store_id);
//            var_dump($accessToken);
//            var_dump($region);

            //DB::execute('UPDATE amazon_order_items LEFT JOIN amazon_order ON amazon_order_items.merchant_id = amazon_order.merchant_id AND amazon_order_items.merchant_store_id = amazon_order.merchant_store_id AND amazon_order_items.order_id = amazon_order.amazon_order_id SET amazon_order_items.marketplace_id = amazon_order.marketplace_id where amazon_order.merchant_id=? and amazon_order.merchant_store_id=? and amazon_order.region=?;', [$merchant_id, $merchant_store_id, $region]);
        });
    }
}
