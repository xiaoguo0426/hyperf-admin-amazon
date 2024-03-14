<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Command\Amazon\FulfillmentInbound;

use AmazonPHP\SellingPartner\AccessToken;
use AmazonPHP\SellingPartner\Exception\ApiException;
use AmazonPHP\SellingPartner\Exception\InvalidArgumentException;
use AmazonPHP\SellingPartner\Marketplace;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use App\Util\AmazonApp;
use App\Util\AmazonSDK;
use App\Util\Log\AmazonFulfillmentInboundGuidanceLog;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Di\Exception\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[Command]
class GetInboundGuidance extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('amazon:fulfillment-inbound:get-inbound-guidance');
    }

    public function configure(): void
    {
        parent::configure();
        $this->addArgument('merchant_id', InputArgument::REQUIRED, '商户id')
            ->addArgument('merchant_store_id', InputArgument::REQUIRED, '店铺id')
            ->addArgument('marketplace_id', InputArgument::REQUIRED, '市场id')
            ->addOption('seller_sku_list', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Seller SKU 列表(英文逗号分隔)')
            ->addOption('asin_list', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'ASIN 列表(英文逗号分隔)')
            ->setDescription('Amazon Fulfillment Inbound Get Inbound Guidance Command');
    }

    /**
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \RedisException
     */
    public function handle(): void
    {
        $merchant_id = (int) $this->input->getArgument('merchant_id');
        $merchant_store_id = (int) $this->input->getArgument('merchant_store_id');
        $marketplace_id = $this->input->getArgument('marketplace_id');
        $seller_sku_list = $this->input->getOption('seller_sku_list'); // --seller_sku_list=foo --seller_sku_list=bar
        $asin_list = $this->input->getOption('asin_list'); // --asin_list=foo --asin_list=bar

        $region = Marketplace::fromId($marketplace_id)->region();

        AmazonApp::tok2($merchant_id, $merchant_store_id, $region, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) use ($marketplace_id, $seller_sku_list, $asin_list) {
            $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
            $logger = ApplicationContext::getContainer()->get(AmazonFulfillmentInboundGuidanceLog::class);

            $retry = 10;
            while (true) {
                try {
                    $response = $sdk->fulfillmentInbound()->getInboundGuidance($accessToken, $region, $marketplace_id, $seller_sku_list, $asin_list);

                    $errorList = $response->getErrors();
                    if (! is_null($errorList)) {
                        foreach ($errorList as $error) {
                            $code = $error->getCode();
                            $msg = $error->getMessage();
                            $detail = $error->getDetails();

                            $log = sprintf('FulfillmentInbound InvalidArgumentException GetBillOfLading Failed. code:%s msg:%s detail:%s merchant_id: %s merchant_store_id: %s ', $code, $msg, $detail, $merchant_id, $merchant_store_id);
                            $console->error($log);
                            $logger->error($log);
                        }
                        break;
                    }

                    $getInboundGuidanceResult = $response->getPayload();
                    if (is_null($getInboundGuidanceResult)) {
                        $console->warning(sprintf('merchant_id:%s merchant_store_id:%s marketplace_id:%s seller_sku_list:%s asin_list:%s API响应 payload为空', $merchant_id, $merchant_store_id, $marketplace_id, implode(',', $seller_sku_list), implode(',', $asin_list)));
                        continue;
                    }
                    $invalidSkuList = $getInboundGuidanceResult->getInvalidSkuList();
                    $invalid_sku_list = [];
                    if (! is_null($invalidSkuList)) {
                        foreach ($invalidSkuList as $invalidSkuItem) {
                            $invalid_sku_list[] = $invalidSkuItem->getSellerSku();
                        }
                    }
                    $invalidAsinList = $getInboundGuidanceResult->getInvalidAsinList();
                    $invalid_asin_list = [];
                    if (! is_null($invalidAsinList)) {
                        foreach ($invalidAsinList as $invalidAsinItem) {
                            $invalid_asin_list[] = $invalidAsinItem->getAsin();
                        }
                    }
                    var_dump($invalid_sku_list);
                    var_dump($invalid_asin_list);

                    $skuInboundGuidanceList = $getInboundGuidanceResult->getSkuInboundGuidanceList();
                    if (! is_null($skuInboundGuidanceList)) {
                        foreach ($skuInboundGuidanceList as $skuInboundGuidanceItem) {
                            $inboundGuidance = $skuInboundGuidanceItem->getInboundGuidance();
                            $inbound_guidance = $inboundGuidance->toString();

                            $seller_sku = $skuInboundGuidanceItem->getSellerSku();
                            $asin = $skuInboundGuidanceItem->getAsin();
                            var_dump($inbound_guidance);
                            var_dump($seller_sku);
                            var_dump($asin);
                        }
                    }

                    $asinInboundGuidanceList = $getInboundGuidanceResult->getAsinInboundGuidanceList();
                    if (! is_null($asinInboundGuidanceList)) {
                        foreach ($asinInboundGuidanceList as $asinInboundGuidanceItem) {
                            $inboundGuidance = $asinInboundGuidanceItem->getInboundGuidance();
                            $inbound_guidance = $inboundGuidance->toString();

                            $asin = $asinInboundGuidanceItem->getAsin();

                            $guidanceReasonList = $asinInboundGuidanceItem->getGuidanceReasonList();
                            if (! is_null($guidanceReasonList)) {
                                foreach ($guidanceReasonList as $guidanceReasonItem) {
                                    $guidance_reason_item = $guidanceReasonItem->toString();
                                    var_dump($guidance_reason_item);
                                }
                            }

                            var_dump($inbound_guidance);
                            var_dump($asin);
                        }
                    }

                    break;
                } catch (ApiException $e) {
                    --$retry;
                    if ($retry > 0) {
                        $console->warning(sprintf('merchant_id:%s merchant_store_id:%s 第 %s 次重试', $merchant_id, $merchant_store_id, $retry));
                        sleep(3);
                        continue;
                    }

                    break;
                } catch (InvalidArgumentException $e) {
                    $console->error(sprintf('merchant_id:%s merchant_store_id:%s InvalidArgumentException %s %s', $merchant_id, $merchant_store_id, $e->getCode(), $e->getMessage()));
                    break;
                }
            }
        });
    }
}
