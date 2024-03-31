<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Command\Amazon\FulfillmentOutbound;

use AmazonPHP\SellingPartner\AccessToken;
use AmazonPHP\SellingPartner\Exception\ApiException;
use AmazonPHP\SellingPartner\Exception\InvalidArgumentException;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use App\Util\AmazonApp;
use App\Util\AmazonSDK;
use App\Util\Log\AmazonFulfillmentOutboundListAllFulfillmentOrdersLog;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\StdoutLoggerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;

#[Command]
class GetFulfillmentPreview extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('amazon:fulfillment-outbound:get-fulfillment-preview');
    }

    public function configure(): void
    {
        parent::configure();
        $this->addArgument('merchant_id', InputArgument::REQUIRED, '商户id')
            ->addArgument('merchant_store_id', InputArgument::REQUIRED, '店铺id')
            ->addArgument('region', InputArgument::REQUIRED, '地区')
            ->setDescription('Amazon Fulfillment Outbound API Get Fulfillment Outbound Preview Command');
    }

    public function handle(): void
    {
        $merchant_id = (int) $this->input->getArgument('merchant_id');
        $merchant_store_id = (int) $this->input->getArgument('merchant_store_id');

        //        AmazonApp::tok($merchant_id, $merchant_store_id, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) {
        //            $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
        //            $logger = ApplicationContext::getContainer()->get(AmazonFulfillmentOutboundListAllFulfillmentOrdersLog::class);
        //
        //
        //            $retry = 10;
        //            $next_token = null;
        //            while (true) {
        //                try {
        //                    $response = $sdk->fulfillmentOutbound()->getFulfillmentPreview($accessToken, $region, $body);
        //
        //                    $errorsList = $response->getErrors();
        //                    if (! is_null($errorsList)) {
        //                        $errors = [];
        //                        foreach ($errorsList as $error) {
        //                            $errors[] = [
        //                                'code' => $error->getCode(),
        //                                'message' => $error->getMessage() ?? '',
        //                                'details' => $error->getDetails() ?? '',
        //                            ];
        //                        }
        //                        $console->error(sprintf('merchant_id:%s merchant_store_id:%s 处理 %s 市场数据发生错误 %s', $merchant_id, $merchant_store_id, $region, json_encode($errors, JSON_THROW_ON_ERROR)));
        //                        break;
        //                    }
        //
        //
        //                } catch (ApiException $e) {
        //                    --$retry;
        //                    if ($retry > 0) {
        //                        $console->warning(sprintf('FulfillmentOutbound ApiException ListAllFulfillmentOrders Failed. retry:%s merchant_id: %s merchant_store_id: %s region:%s ', $retry, $merchant_id, $merchant_store_id, $region));
        //                        sleep(10);
        //                        continue;
        //                    }
        //
        //                    $log = sprintf('FulfillmentOutbound ApiException ListAllFulfillmentOrders Failed. merchant_id: %s merchant_store_id: %s region:%s', $merchant_id, $merchant_store_id, $region);
        //                    $console->error($log);
        //                    $logger->error($log);
        //                    break;
        //                } catch (InvalidArgumentException $e) {
        //                    $log = sprintf('FulfillmentOutbound InvalidArgumentException ListAllFulfillmentOrders Failed. merchant_id: %s merchant_store_id: %s region:%s', $merchant_id, $merchant_store_id, $region);
        //                    $console->error($log);
        //                    $logger->error($log);
        //                    break;
        //                }
        //            }
        //        });
    }
}
