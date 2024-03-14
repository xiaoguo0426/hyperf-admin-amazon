<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Util\Amazon\Engine;

use AmazonPHP\SellingPartner\AccessToken;
use AmazonPHP\SellingPartner\Exception\ApiException;
use AmazonPHP\SellingPartner\Exception\InvalidArgumentException;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use App\Util\Amazon\Action\FinancialEventsAction;
use App\Util\Amazon\Creator\CreatorInterface;
use App\Util\Amazon\Creator\ListFinancialEventsByGroupIdCreator;
use App\Util\AmazonSDK;
use App\Util\Log\AmazonFinanceLog;
use App\Util\RuntimeCalculator;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\StdoutLoggerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

use function Hyperf\Support\make;

class ListFinancialEventsByGroupIdEngine implements EngineInterface
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \JsonException
     */
    public function launch(AmazonSDK $amazonSDK, SellingPartnerSDK $sdk, AccessToken $accessToken, CreatorInterface $creator): bool
    {
        $merchant_id = $amazonSDK->getMerchantId();
        $merchant_store_id = $amazonSDK->getMerchantStoreId();

        /**
         * @var ListFinancialEventsByGroupIdCreator $creator
         */
        $group_id = $creator->getGroupId();
        $max_results_per_page = $creator->getMaxResultsPerPage();
        $posted_after = $creator->getPostedAfter();
        $posted_before = $creator->getPostedBefore();

        $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
        $logger = ApplicationContext::getContainer()->get(AmazonFinanceLog::class);

        $runtimeCalculator = new RuntimeCalculator();
        $runtimeCalculator->start();

        $console->info(sprintf('当前财务组id:%s merchant_id:%s merchant_store_id:%s', $group_id, $merchant_id, $merchant_store_id));

        $retry = 10;
        $next_token = null;

        $region = $amazonSDK->getRegion();

        while (true) {
            try {
                // 指定日期范围内的财务事件组
                $response = $sdk->finances()->listFinancialEventsByGroupId($accessToken, $region, $group_id, $max_results_per_page, $posted_after, $posted_before, $next_token);

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
                // TODO 拉取一页就处理一页数据，这里可能会有点问题。如果处理时间过长，可能会导致next_token过期
                make(FinancialEventsAction::class, [$merchant_id, $merchant_store_id, $financialEvents])->run();

                // 如果下一页没有数据，nextToken 会变成null
                $next_token = $payload->getNextToken();
                if (is_null($next_token)) {
                    $console->info(sprintf('当前财务组id:%s数据已处理完成 merchant_id:%s merchant_store_id:%s', $group_id, $merchant_id, $merchant_store_id));
                    break;
                }
            } catch (ApiException $e) {
                $can_retry_flag = true;
                $response_body = $e->getResponseBody();
                if (! is_null($response_body)) {
                    $body = json_decode($response_body, true, 512, JSON_THROW_ON_ERROR);
                    if (isset($body['errors'])) {
                        $errors = $body['errors'];
                        foreach ($errors as $error) {
                            $code = $error['code'];
                            $message = $error['message'];
                            $details = $error['details'];
                            $console->error(sprintf('ApiException Code:%s Message:%s', $code, $message));
                            if ($code === 'InvalidInput') {
                                $console->error('当前错误无法重试，请检查请求参数. ');
                                $can_retry_flag = false;
                                break;
                            }
                        }
                    }
                }
                if (! $can_retry_flag) {
                    break;
                }

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

        $console->notice(sprintf('当前财务组id:%s 处理完成,耗时:%s  merchant_id:%s merchant_store_id:%s ', $group_id, $runtimeCalculator->stop(), $merchant_id, $merchant_store_id));
        return true;
    }
}
