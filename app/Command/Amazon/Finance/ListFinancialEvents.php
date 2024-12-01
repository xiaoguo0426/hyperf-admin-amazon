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
use AmazonPHP\SellingPartner\Exception\ApiException;
use AmazonPHP\SellingPartner\Exception\InvalidArgumentException;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use App\Util\Amazon\Action\FinancialEventsAction;
use App\Util\AmazonApp;
use App\Util\AmazonSDK;
use App\Util\Log\AmazonFinanceLog;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\StdoutLoggerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Input\InputArgument;

use function Hyperf\Support\make;

#[Command]
class ListFinancialEvents extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('amazon:finance:list-financial-events');
    }

    public function configure(): void
    {
        parent::configure();
        $this->addArgument('merchant_id', InputArgument::REQUIRED, '商户id')
            ->addArgument('merchant_store_id', InputArgument::REQUIRED, '店铺id')
            ->addArgument('region', InputArgument::REQUIRED, '地区')
            ->addArgument('posted_after', InputArgument::REQUIRED, '指定时间之后（或在指定时间）发布的财务事件的日期')
            ->addArgument('posted_before', InputArgument::OPTIONAL, '指定时间之前（但不是在指定时间）发布的财务事件的日期')
            ->setDescription('Amazon Finance List Financial Events Command');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \RedisException
     */
    public function handle(): void
    {
        $merchant_id = (int) $this->input->getArgument('merchant_id');
        $merchant_store_id = (int) $this->input->getArgument('merchant_store_id');
        $region = $this->input->getArgument('region');
        $posted_after = $this->input->getArgument('posted_after');
        $posted_before = $this->input->getArgument('posted_before');

        AmazonApp::tok2($merchant_id, $merchant_store_id, $region, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) use ($posted_after, $posted_before) {
            $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
            $logger = ApplicationContext::getContainer()->get(AmazonFinanceLog::class);

            $retry = 10;

            $max_results_per_page = 100;
            $posted_after = new \DateTime($posted_after, new \DateTimeZone('UTC'));
            $posted_before = $posted_before ? (new \DateTime($posted_before, new \DateTimeZone('UTC'))) : null;
            $next_token = null;

            while (true) {
                try {
                    // 指定日期范围内的财务事件组
                    $response = $sdk->finances()->listFinancialEvents($accessToken, $region, $max_results_per_page, $posted_after, $posted_before, $next_token);

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

                    make(FinancialEventsAction::class, [$merchant_id, $merchant_store_id, $financialEvents])->run();

                    // 如果下一页没有数据，nextToken 会变成null
                    $next_token = $payload->getNextToken();
                    if (is_null($next_token)) {
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
                        $console->warning(sprintf('Finance ApiException listFinancialEventGroups Failed. retry:%s merchant_id: %s merchant_store_id: %s ', $retry, $merchant_id, $merchant_store_id));
                        sleep(10);
                        continue;
                    }

                    $log = sprintf('Finance ApiException listFinancialEventGroups Failed. merchant_id: %s merchant_store_id: %s ', $merchant_id, $merchant_store_id);
                    $console->error($log);
                    $logger->error($log);
                    break;
                } catch (InvalidArgumentException $e) {
                    $log = sprintf('Finance InvalidArgumentException listFinancialEventGroups Failed. merchant_id: %s merchant_store_id: %s ', $merchant_id, $merchant_store_id);
                    $console->error($log);
                    $logger->error($log);
                    break;
                }
            }

            return true;
        });
    }
}
