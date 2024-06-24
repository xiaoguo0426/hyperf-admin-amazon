<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Command\Amazon\DataKiosk;

use AmazonPHP\SellingPartner\AccessToken;
use AmazonPHP\SellingPartner\Exception\ApiException;
use AmazonPHP\SellingPartner\Exception\InvalidArgumentException;
use AmazonPHP\SellingPartner\Model\DataKiosk\CreateQuerySpecification;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use App\Util\AmazonApp;
use App\Util\AmazonSDK;
use App\Util\Log\AmazonFbaInventoryLog;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Di\Exception\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Input\InputArgument;

#[Command]
class CreateQuery extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('amazon:data-kiosk:create-query');
    }

    public function configure(): void
    {
        parent::configure();
        $this->addArgument('merchant_id', InputArgument::REQUIRED, '商户id')
            ->addArgument('merchant_store_id', InputArgument::REQUIRED, '店铺id')
            ->addArgument('region', InputArgument::REQUIRED, '地区')
            ->setDescription('Amazon Data Kiosk Create Query Command');
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
        $region = $this->input->getArgument('region');

        AmazonApp::tok2($merchant_id, $merchant_store_id, $region, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) {
            $logger = ApplicationContext::getContainer()->get(AmazonFbaInventoryLog::class);
            $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);

            $retry = 10;

            $pagination_token = null;
            $query = 'query MyQuery{analytics_salesAndTraffic_2023_11_15{salesAndTrafficByAsin(aggregateBy:CHILD endDate:"" startDate:"" marketplaceIds:[""]){childAsin endDate marketplaceId parentAsin sales{orderedProductSales{amount currencyCode}orderedProductSalesB2B{amount currencyCode}totalOrderItems totalOrderItemsB2B unitsOrdered unitsOrderedB2B}sku startDate traffic{browserPageViews browserPageViewsB2B browserPageViewsPercentage browserPageViewsPercentageB2B browserSessionPercentage browserSessionPercentageB2B browserSessionsB2B browserSessions buyBoxPercentage buyBoxPercentageB2B mobileAppPageViews mobileAppPageViewsB2B mobileAppPageViewsPercentageB2B mobileAppPageViewsPercentage mobileAppSessionPercentageB2B}}}}';

            while (true) {
                try {
                    $body = new CreateQuerySpecification();
                    $body->setQuery($query);
                    $body->setPaginationToken($pagination_token);

                    $response = $sdk->dataKiosk()->createQuery($accessToken, $region, $body);
                    $query_id = $response->getQueryId();
                    var_dump($query_id);
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
