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
use App\Util\Amazon\Creator\ListFinancialEventsByOrderIdCreator;
use App\Util\AmazonSDK;
use App\Util\Log\AmazonFinanceLog;
use App\Util\RuntimeCalculator;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\StdoutLoggerInterface;
use JsonException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ListFinancialEventsByOrderIdEngine implements EngineInterface
{
    /**
     * @param AmazonSDK $amazonSDK
     * @param SellingPartnerSDK $sdk
     * @param AccessToken $accessToken
     * @param CreatorInterface $creator
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     * @return bool
     */
    public function launch(AmazonSDK $amazonSDK, SellingPartnerSDK $sdk, AccessToken $accessToken, CreatorInterface $creator): bool
    {
        $merchant_id = $amazonSDK->getMerchantId();
        $merchant_store_id = $amazonSDK->getMerchantStoreId();

        /**
         * @var ListFinancialEventsByOrderIdCreator $creator
         */
        $amazon_order_id = $creator->getOrderId();
        $max_results_per_page = $creator->getMaxResultsPerPage();

        $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
        $logger = ApplicationContext::getContainer()->get(AmazonFinanceLog::class);

        $runtimeCalculator = new RuntimeCalculator();
        $runtimeCalculator->start();

        $console->info(sprintf('当前订单id:%s merchant_id:%s merchant_store_id:%s', $amazon_order_id, $merchant_id, $merchant_store_id));

        $retry = 10;
        $next_token = null;

        $region = $amazonSDK->getRegion();

        $page = 1;

        while (true) {
            try {
                $response = $sdk->finances()->listFinancialEventsByOrderId($accessToken, $region, $amazon_order_id, $max_results_per_page, $next_token);
                $payload = $response->getPayload();
                if ($payload === null) {
                    $console->warning(sprintf('merchant_id:%s merchant_store_id:%s payload为null', $merchant_id, $merchant_store_id));
                    break;
                }
                $errorsList = $response->getErrors();
                if (! is_null($errorsList)) {
                    $errors = [];
                    foreach ($errorsList as $error) {
                        $errors[] = [
                            'code' => $error->getCode(),
                            'message' => $error->getMessage() ?? '',
                            'details' => $error->getDetails() ?? '',
                        ];
                    }
                    $console->error(sprintf('merchant_id:%s merchant_store_id:%s 发生错误 %s', $merchant_id, $merchant_store_id, json_encode($errors, JSON_THROW_ON_ERROR)));
                    break;
                }

                $financialEvents = $payload->getFinancialEvents();
                if (is_null($financialEvents)) {
                    break;
                }

                \Hyperf\Support\make(FinancialEventsAction::class, [$merchant_id, $merchant_store_id, $financialEvents])->run();

                $next_token = $payload->getNextToken();
                if (is_null($next_token)) {
                    break;
                }
            } catch (ApiException $exception) {
                if (! is_null($exception->getResponseBody())) {
                    $body = json_decode($exception->getResponseBody(), true, 512, JSON_THROW_ON_ERROR);
                    if (isset($body['errors'])) {
                        $errors = $body['errors'];
                        foreach ($errors as $error) {
                            if ($error['code'] !== 'QuotaExceeded') {
                                $console->warning(sprintf('merchant_id:%s merchant_store_id:%s Page:%s code:%s message:%s', $merchant_id, $merchant_store_id, $page, $error['code'], $error['message']));
                                break 2;
                            }
                        }
                    }
                }

                --$retry;
                if ($retry > 0) {
                    $console->warning(sprintf('merchant_id:%s merchant_store_id:%s Page:%s 第 %s 次重试', $merchant_id, $merchant_store_id, $page, $retry));
                    sleep(3);
                    continue;
                }

                continue;
            } catch (InvalidArgumentException $exception) {
                $log = sprintf('merchant_id:%s merchant_store_id:%s InvalidArgumentException ', $merchant_id, $merchant_store_id);
                $console->error($log);
                $logger->error($log);
                break;
            }

            $retry = 30; // 重置重试次数
            ++$page;
        }

        $console->notice(sprintf('当前订单id:%s 处理完成,耗时:%s  merchant_id:%s merchant_store_id:%s ', $amazon_order_id, $runtimeCalculator->stop(), $merchant_id, $merchant_store_id));
        return true;
    }
}
