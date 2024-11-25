<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Command\Amazon\Replenishment;

use AmazonPHP\SellingPartner\AccessToken;
use AmazonPHP\SellingPartner\Exception\ApiException;
use AmazonPHP\SellingPartner\Exception\InvalidArgumentException;
use AmazonPHP\SellingPartner\Model\Replenishment\ListOffersRequest;
use AmazonPHP\SellingPartner\Model\Replenishment\ListOffersRequestFilters;
use AmazonPHP\SellingPartner\Model\Replenishment\ListOffersRequestPagination;
use AmazonPHP\SellingPartner\Model\Replenishment\ListOffersRequestSort;
use AmazonPHP\SellingPartner\Model\Replenishment\ListOffersSortKey;
use AmazonPHP\SellingPartner\Model\Replenishment\SortOrder;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use App\Util\AmazonApp;
use App\Util\AmazonSDK;
use App\Util\Log\AmazonFbaInventoryLog;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\StdoutLoggerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;

#[Command]
class ListOffers extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('amazon:replenishment:list-offers');
    }

    public function configure(): void
    {
        parent::configure();
        $this->addArgument('merchant_id', InputArgument::REQUIRED, '商户id')
            ->addArgument('merchant_store_id', InputArgument::REQUIRED, '店铺id')
            ->addArgument('region', InputArgument::REQUIRED, '地区')
            ->setDescription('Amazon Replenishment ListOffers Command');
    }

    public function handle(): void
    {
        $merchant_id = (int) $this->input->getArgument('merchant_id');
        $merchant_store_id = (int) $this->input->getArgument('merchant_store_id');
        $region = $this->input->getArgument('region');
        return ;
        //暂时注释
        AmazonApp::tok2($merchant_id, $merchant_store_id, $region, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) {
            $logger = ApplicationContext::getContainer()->get(AmazonFbaInventoryLog::class);
            $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);

            $retry = 10;

            while (true) {
                try {

                    $body = new ListOffersRequest();

                    $pagination = new ListOffersRequestPagination();
                    $pagination->setLimit(100);//TODO
                    $pagination->setOffset(100);//TODO
                    $body->setPagination($pagination);

                    $listOffersRequestFilters = new ListOffersRequestFilters();
                    $listOffersRequestFilters->setMarketplaceId();//TODO
                    $listOffersRequestFilters->setSkus();//TODO
                    $listOffersRequestFilters->setAsins();//TODO
                    $listOffersRequestFilters->setEligibilities();//TODO
                    $listOffersRequestFilters->setPreferences();//TODO
                    $listOffersRequestFilters->setPromotions();//TODO
                    $listOffersRequestFilters->setProgramTypes();//TODO
                    $body->setFilters($listOffersRequestFilters);

                    $listOffersRequestSort = new ListOffersRequestSort();
                    $sortOrder = new SortOrder('DESC');//TODO
                    $listOffersRequestSort->setOrder($sortOrder);
                    $listOffersSortKey = new ListOffersSortKey('ASIN');//TODO
                    $listOffersRequestSort->setKey($listOffersSortKey);
                    $body->setSort($listOffersRequestSort);

                    $response = $sdk->offersReplenishment()->listOffers($accessToken, $region, $body);
                    $offers = $response->getOffers();
                    var_dump($offers);
                    $pagination = $response->getPagination();
                    $total_results = $pagination->getTotalResults();

                    var_dump($total_results);
                    break;
                } catch (ApiException $exception) {
                    --$retry;
                    if ($retry > 0) {
                        $console->warning(sprintf('ApiException Inventory API retry:%s Exception:%s', $retry, $exception->getMessage()));
                        sleep(10);
                        continue;
                    }
                    $console->error('ApiException DataKiosk CreateQuery API 重试次数耗尽', [
                        'message' => $exception->getMessage(),
                        'trace' => $exception->getTraceAsString(),
                    ]);

                    $logger->error('ApiException DataKiosk CreateQuery API 重试次数耗尽', [
                        'message' => $exception->getMessage(),
                        'trace' => $exception->getTraceAsString(),
                    ]);

                } catch (InvalidArgumentException $exception) {
                    $console->error('InvalidArgumentException DataKiosk CreateQuery API请求错误', [
                        'message' => $exception->getMessage(),
                        'trace' => $exception->getTraceAsString(),
                    ]);

                    $logger->error('InvalidArgumentException DataKiosk CreateQuery API请求错误', [
                        'message' => $exception->getMessage(),
                        'trace' => $exception->getTraceAsString(),
                    ]);
                    break;
                }
            }
            return true;
        });

    }
}
