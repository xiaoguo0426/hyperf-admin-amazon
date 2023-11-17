<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Command\Amazon\Finance;

use AmazonPHP\SellingPartner\AccessToken;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use App\Model\AmazonOrderModel;
use App\Util\Amazon\Creator\ListFinancialEventsByOrderIdCreator;
use App\Util\Amazon\Engine\ListFinancialEventsByOrderIdEngine;
use App\Util\AmazonApp;
use App\Util\AmazonSDK;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Input\InputArgument;

#[Command]
class ListFinancialEventsByOrderId extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('amazon:finance:list-financial-events-by-order-id');
    }

    public function configure(): void
    {
        parent::configure();
        $this->addArgument('merchant_id', InputArgument::REQUIRED, '商户id')
            ->addArgument('merchant_store_id', InputArgument::REQUIRED, '店铺id')
            ->addArgument('order_id', InputArgument::REQUIRED, '订单id')
            ->setDescription('Amazon Finance List Financial Events By Order Id Command');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function handle(): void
    {
        $merchant_id = (int) $this->input->getArgument('merchant_id');
        $merchant_store_id = (int) $this->input->getArgument('merchant_store_id');
        $amazon_order_ids = $this->input->getArgument('order_id');

        AmazonApp::tok($merchant_id, $merchant_store_id, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) use ($amazon_order_ids) {
            if (! is_null($amazon_order_ids)) {
                $amazon_order_ids = explode(',', $amazon_order_ids);
            }

            $amazonOrderCollections = AmazonOrderModel::query()
                ->where('merchant_id', $merchant_id)
                ->where('merchant_store_id', $merchant_store_id)
                ->when($amazon_order_ids, static function ($query, $value) {
                    return $query->whereIn('amazon_order_id', $value);
                })->get();
            if ($amazonOrderCollections->isEmpty()) {
                return true;
            }

            /**
             * @var AmazonOrderModel $amazonOrderCollection
             */
            foreach ($amazonOrderCollections as $amazonOrderCollection) {
                $amazon_order_id = $amazonOrderCollection->amazon_order_id;

                $creator = new ListFinancialEventsByOrderIdCreator();
                $creator->setOrderId($amazon_order_id);
                $creator->setMaxResultsPerPage(100);

                \Hyperf\Support\make(ListFinancialEventsByOrderIdEngine::class)->launch($amazonSDK, $sdk, $accessToken, $creator);
            }

            return true;
        });
    }
}
