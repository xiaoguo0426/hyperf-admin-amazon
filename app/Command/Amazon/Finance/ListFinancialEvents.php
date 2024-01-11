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
use Symfony\Component\Console\Input\InputOption;
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
            ->addOption('posted_after', null, InputOption::VALUE_OPTIONAL, '指定时间之后（或在指定时间）发布的财务事件的日期', null)
            ->addOption('posted_before', null, InputOption::VALUE_OPTIONAL, '指定时间之前（但不是在指定时间）发布的财务事件的日期', null)
            ->setDescription('Amazon Finance List Financial Events Command');
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \RedisException
     * @return void
     */
    public function handle(): void
    {
        $merchant_id = (int) $this->input->getArgument('merchant_id');
        $merchant_store_id = (int) $this->input->getArgument('merchant_store_id');
        $posted_after = $this->input->getOption('posted_after');
        $posted_before = $this->input->getOption('posted_before');

        AmazonApp::tok($merchant_id, $merchant_store_id, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) use ($posted_after, $posted_before) {
            $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
            $logger = ApplicationContext::getContainer()->get(AmazonFinanceLog::class);

            $retry = 10;

            $max_results_per_page = 100;
            $posted_after = $posted_after ? (new \DateTime($posted_after, new \DateTimeZone('UTC'))) : null;
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
