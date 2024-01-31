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
use App\Util\Log\AmazonFulfillmentOutboundListReturnReasonCodesLog;
use DateTime;
use DateTimeZone;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Di\Exception\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RedisException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[Command]
class ListReturnReasonCodes extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('amazon:fulfillment-outbound:list-return-reason-codes');
    }

    public function configure(): void
    {
        parent::configure();
        $this->addArgument('merchant_id', InputArgument::REQUIRED, '商户id')
            ->addArgument('merchant_store_id', InputArgument::REQUIRED, '店铺id')
            ->addArgument('region', InputArgument::REQUIRED, '地区')
            ->addArgument('seller_sku', InputArgument::REQUIRED, 'Seller SKU')
            ->addArgument('language', InputArgument::REQUIRED, 'Language')//en_US,fr_CA,fr_FR 这个数未测试
            ->addOption('marketplace_id', null, InputOption::VALUE_OPTIONAL, '市场id', null)
            ->addOption('seller_fulfillment_order_id', null, InputOption::VALUE_OPTIONAL, '创建履行订单时卖家分配给商品的标识符。该服务使用此值来确定卖家想要返回原因代码的市场。', null)
            ->setDescription('Amazon Fulfillment Outbound API List Return Reason Codes Command');
    }

    /**
     * @throws NotFoundException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     * @return void
     */
    public function handle(): void
    {

        $merchant_id = (int) $this->input->getArgument('merchant_id');
        $merchant_store_id = (int) $this->input->getArgument('merchant_store_id');
        $region = $this->input->getArgument('region');
        $seller_sku = $this->input->getArgument('seller_sku');
        $language = $this->input->getArgument('language');
        $marketplace_id = $this->input->getOption('marketplace_id');
        $seller_fulfillment_order_id = $this->input->getOption('seller_fulfillment_order_id');

        AmazonApp::tok2($merchant_id, $merchant_store_id, $region, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) use ($seller_sku, $language, $marketplace_id, $seller_fulfillment_order_id) {

            $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
            $logger = ApplicationContext::getContainer()->get(AmazonFulfillmentOutboundListReturnReasonCodesLog::class);

            $retry = 10;
            //当前接口没有测试通过
            while (true) {

                try {

                    $response = $sdk->fulfillmentOutbound()->listReturnReasonCodes($accessToken, $region, $seller_sku, $language, $marketplace_id, $seller_fulfillment_order_id);

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
                        $console->error(sprintf('merchant_id:%s merchant_store_id:%s 处理 %s 市场数据发生错误 %s', $merchant_id, $merchant_store_id, $region, json_encode($errors, JSON_THROW_ON_ERROR)));
                        break;
                    }

                    $listReturnReasonCodesResult = $response->getPayload();
                    if (is_null($listReturnReasonCodesResult)) {
                        break;
                    }

                    $reasonCodeDetails = $listReturnReasonCodesResult->getReasonCodeDetails();
                    if (is_null($reasonCodeDetails)) {
                        break;
                    }
                    $reason_code_details = [];
                    foreach ($reasonCodeDetails as $reasonCodeDetail) {
                        $return_reason_code = $reasonCodeDetail->getReturnReasonCode();
                        $description = $reasonCodeDetail->getDescription();
                        $translated_description = $reasonCodeDetail->getTranslatedDescription() ?? '';

                        $reason_code_details[] = [
                            'return_reason_code' => $return_reason_code,
                            'description' => $description,
                            'translated_description' => $translated_description,
                        ];
                    }
                    var_dump($reason_code_details);

                    break;
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
                        $console->warning(sprintf('FulfillmentOutbound ApiException ListAllFulfillmentOrders Failed. retry:%s merchant_id: %s merchant_store_id: %s region:%s ', $retry, $merchant_id, $merchant_store_id, $region));
                        sleep(10);
                        continue;
                    }

                    $log = sprintf('FulfillmentOutbound ApiException ListAllFulfillmentOrders Failed. merchant_id: %s merchant_store_id: %s region:%s', $merchant_id, $merchant_store_id, $region);
                    $console->error($log);
                    $logger->error($log);
                    break;
                } catch (InvalidArgumentException $e) {
                    $log = sprintf('FulfillmentOutbound InvalidArgumentException ListAllFulfillmentOrders Failed. merchant_id: %s merchant_store_id: %s region:%s', $merchant_id, $merchant_store_id, $region);
                    $console->error($log);
                    $logger->error($log);
                    break;
                }

            }
        });
    }
}
