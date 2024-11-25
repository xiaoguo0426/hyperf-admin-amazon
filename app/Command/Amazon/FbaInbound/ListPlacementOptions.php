<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Command\Amazon\FbaInbound;

use AmazonPHP\SellingPartner\AccessToken;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use App\Util\Amazon\Creator\ListPlacementOptionsCreator;
use App\Util\Amazon\Engine\ListPlacementOptionsEngine;
use App\Util\AmazonApp;
use App\Util\AmazonSDK;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Di\Exception\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Input\InputArgument;

use function Hyperf\Support\make;

#[Command]
class ListPlacementOptions extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('amazon:fba-inbound:list-placement-options');
    }

    public function configure(): void
    {
        parent::configure();
        $this->addArgument('merchant_id', InputArgument::REQUIRED, '商户id')
            ->addArgument('merchant_store_id', InputArgument::REQUIRED, '店铺id')
            ->addArgument('region', InputArgument::REQUIRED, '地区')
            ->addArgument('inbound_plan_ids', InputArgument::REQUIRED, '入站计划id列表')
            ->setDescription('Amazon Fulfillment Inbound ListPlacementOptions Command');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundException
     * @throws NotFoundExceptionInterface
     * @throws \RedisException
     */
    public function handle(): void
    {
        $merchant_id = (int) $this->input->getArgument('merchant_id');
        $merchant_store_id = (int) $this->input->getArgument('merchant_store_id');
        $region = $this->input->getArgument('region');
        $inbound_plan_ids = $this->input->getArgument('inbound_plan_ids');
        return;
        //该API已废弃
        AmazonApp::tok2($merchant_id, $merchant_store_id, $region, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) use ($inbound_plan_ids) {
            $inbound_plan_ids_list = explode(',', $inbound_plan_ids);
            foreach ($inbound_plan_ids_list as $inbound_plan_id) {
                $creator = new ListPlacementOptionsCreator();
                $creator->setInboundPlanId($inbound_plan_id);

                make(ListPlacementOptionsEngine::class, [$amazonSDK, $sdk, $accessToken])->launch($creator);
            }

            return true;
        });
    }
}
