<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Queue;

use AmazonPHP\SellingPartner\AccessToken;
use AmazonPHP\SellingPartner\Exception\ApiException;
use AmazonPHP\SellingPartner\Exception\InvalidArgumentException;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use App\Queue\Data\AmazonFinanceListFinancialEventsByGroupIdData;
use App\Queue\Data\QueueDataInterface;
use App\Util\Amazon\Action\FinancialEventsAction;
use App\Util\AmazonApp;
use App\Util\AmazonSDK;
use App\Util\Log\AmazonFinanceLog;
use App\Util\RuntimeCalculator;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\StdoutLoggerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class AmazonFinanceFinancialListEventsByGroupIdQueue extends Queue
{
    public function getQueueName(): string
    {
        return 'amazon-financial-list-events-by-group-id';
    }

    public function getQueueDataClass(): string
    {
        return AmazonFinanceListFinancialEventsByGroupIdData::class;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function handleQueueData(QueueDataInterface $queueData): bool
    {
        /**
         * @var AmazonFinanceListFinancialEventsByGroupIdData $queueData
         */
        $merchant_id = $queueData->getMerchantId();
        $merchant_store_id = $queueData->getMerchantStoreId();
        $financial_event_group_id = $queueData->getFinancialEventGroupId();

        AmazonApp::tok($merchant_id, $merchant_store_id, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) use ($financial_event_group_id) {
            $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
            $logger = ApplicationContext::getContainer()->get(AmazonFinanceLog::class);

            $runtimeCalculator = new RuntimeCalculator();
            $runtimeCalculator->start();

            $console->info(sprintf('当前财务组id:%s merchant_id:%s merchant_store_id:%s', $financial_event_group_id, $merchant_id, $merchant_store_id));

            $retry = 10;
            $max_results_per_page = 100;
            $next_token = null;

            while (true) {
                try {
                    // 指定日期范围内的财务事件组
                    $response = $sdk->finances()->listFinancialEventsByGroupId($accessToken, $region, $financial_event_group_id, $max_results_per_page, null, null, $next_token);

                    $errorList = $response->getErrors();
                    if (! is_null($errorList)) {
                        foreach ($errorList as $error) {
                            $code = $error->getCode();
                            $msg = $error->getMessage();
                            $detail = $error->getDetails();

                            $log = sprintf('Finance InvalidArgumentException listFinancialEventGroups Failed. code:%s msg:%s detail:%s merchant_id: %s merchant_store_id: %s ', $code, $msg, $detail, $merchant_id, $merchant_store_id);
                            $console->error($log);
                            $logger->error($log);
                        }
                        break;
                    }

                    $payload = $response->getPayload();
                    if (is_null($payload)) {
                        break;
                    }
                    $financialEvents = $payload->getFinancialEvents();
                    if (is_null($financialEvents)) {
                        break;
                    }

                    \Hyperf\Support\make(FinancialEventsAction::class, [$merchant_id, $merchant_store_id, $financialEvents])->run();

                    // 如果下一页没有数据，nextToken 会变成null
                    $next_token = $payload->getNextToken();
                    if (is_null($next_token)) {
                        $console->info(sprintf('当前财务组id:%s数据已处理完成 merchant_id:%s merchant_store_id:%s', $financial_event_group_id, $merchant_id, $merchant_store_id));
                        break;
                    }
                } catch (ApiException $e) {
                    --$retry;
                    if ($retry > 0) {
                        $console->warning(sprintf('Finance ApiException listFinancialEventsByGroupId Failed. retry:%s merchant_id: %s merchant_store_id: %s ', $retry, $merchant_id, $merchant_store_id));
                        sleep(10);
                        continue;
                    }
                    break;
                } catch (InvalidArgumentException $e) {
                    $log = sprintf('Finance InvalidArgumentException listFinancialEventsByGroupId Failed. merchant_id: %s merchant_store_id: %s ', $merchant_id, $merchant_store_id);
                    $console->error($log);
                    $logger->error($log);
                    break;
                }
            }

            $console->notice(sprintf('当前财务组id:%s 处理完成,耗时:%s  merchant_id:%s merchant_store_id:%s ', $financial_event_group_id, $runtimeCalculator->stop(), $merchant_id, $merchant_store_id));
            return true;
        });

        return true;
    }

    public function safetyLine(): int
    {
        return 70;
    }
}
