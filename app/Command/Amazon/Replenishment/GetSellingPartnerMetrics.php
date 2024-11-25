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
use AmazonPHP\SellingPartner\Model\Replenishment\AggregationFrequency;
use AmazonPHP\SellingPartner\Model\Replenishment\GetSellingPartnerMetricsRequest;
use AmazonPHP\SellingPartner\Model\Replenishment\ListOffersRequest;
use AmazonPHP\SellingPartner\Model\Replenishment\ListOffersRequestFilters;
use AmazonPHP\SellingPartner\Model\Replenishment\ListOffersRequestPagination;
use AmazonPHP\SellingPartner\Model\Replenishment\ListOffersRequestSort;
use AmazonPHP\SellingPartner\Model\Replenishment\ListOffersSortKey;
use AmazonPHP\SellingPartner\Model\Replenishment\Metric;
use AmazonPHP\SellingPartner\Model\Replenishment\ProgramType;
use AmazonPHP\SellingPartner\Model\Replenishment\SortOrder;
use AmazonPHP\SellingPartner\Model\Replenishment\TimeInterval;
use AmazonPHP\SellingPartner\Model\Replenishment\TimePeriodType;
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
class GetSellingPartnerMetrics extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('amazon:replenishment:get-selling-partner-metrics');
    }

    public function configure(): void
    {
        parent::configure();
        $this->addArgument('merchant_id', InputArgument::REQUIRED, '商户id')
            ->addArgument('merchant_store_id', InputArgument::REQUIRED, '店铺id')
            ->addArgument('region', InputArgument::REQUIRED, '地区')
            ->setDescription('Amazon Replenishment GetSellingPartnerMetrics Command');
    }

    public function handle(): void
    {
        $merchant_id = (int) $this->input->getArgument('merchant_id');
        $merchant_store_id = (int) $this->input->getArgument('merchant_store_id');
        $region = $this->input->getArgument('region');
        return;
        //待处理
        AmazonApp::tok2($merchant_id, $merchant_store_id, $region, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) {
            $logger = ApplicationContext::getContainer()->get(AmazonFbaInventoryLog::class);
            $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);

            $retry = 10;

            while (true) {
                try {
                    $body = new GetSellingPartnerMetricsRequest();

                    $aggregationFrequency = new AggregationFrequency('WEEK');
                    $body->setAggregationFrequency($aggregationFrequency);

                    $timeInterval = new TimeInterval();
                    $timeInterval->setStartDate();
                    $timeInterval->setEndDate();
                    $body->setTimeInterval($timeInterval);

                    $body->setMetrics([
                        new Metric(Metric::SHIPPED_SUBSCRIPTION_UNITS)
                    ]);

                    $timePeriodType = new TimePeriodType(TimePeriodType::PERFORMANCE);
                    $body->setTimePeriodType($timePeriodType);

                    $body->setMarketplaceId();//TODO

                    $body->setProgramTypes([
                        new ProgramType(ProgramType::SUBSCRIBE_AND_SAVE)
                    ]);

                    $response = $sdk->sellingPartnersReplenishment()->getSellingPartnerMetrics($accessToken, $region, $body);

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
