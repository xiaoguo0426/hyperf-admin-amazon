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
use RedisException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[Command]
class GetPreInstructions extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('amazon:fulfillment-inbound:get-pre-instructions');
    }

    public function configure(): void
    {
        parent::configure();
        $this->addArgument('merchant_id', InputArgument::REQUIRED, '商户id')
            ->addArgument('merchant_store_id', InputArgument::REQUIRED, '店铺id')
            ->addArgument('ship_to_country_code', InputArgument::REQUIRED, '国家二字码')
            ->addOption('seller_sku_list', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Seller SKU 列表(英文逗号分隔)')
            ->addOption('asin_list', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'ASIN 列表(英文逗号分隔)')
            ->setDescription('Amazon Fulfillment Inbound Get PreInstructions Command');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     * @return void
     */
    public function handle(): void
    {
        //返回标签要求和物品准备说明，以帮助准备物品以运送到亚马逊的履行网络。
        $merchant_id = (int) $this->input->getArgument('merchant_id');
        $merchant_store_id = (int) $this->input->getArgument('merchant_store_id');
        $country_code = $this->input->getArgument('ship_to_country_code');
        $seller_sku_list = $this->input->getOption('seller_sku_list');//--seller_sku_list=foo --seller_sku_list=bar
        $asin_list = $this->input->getOption('asin_list');//--asin_list=foo --asin_list=bar

        $region = Marketplace::fromCountry($country_code)->region();

        AmazonApp::tok2($merchant_id, $merchant_store_id, $region, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) use ($country_code, $seller_sku_list, $asin_list) {

            $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
            $logger = ApplicationContext::getContainer()->get(AmazonFulfillmentInboundGuidanceLog::class);

            $retry = 10;
            while (true) {
                try {
                    $response = $sdk->fulfillmentInbound()->getPrepInstructions($accessToken, $region, $country_code, $seller_sku_list, $asin_list);

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


                    $skuPrepInstructions = $getInboundGuidanceResult->getSkuPrepInstructionsList();
                    $sku_pre_instructions = [];
                    if (! is_null($skuPrepInstructions)) {
                        foreach ($skuPrepInstructions as $skuPrepInstruction) {
                            $prepInstructions = $skuPrepInstruction->getPrepInstructionList();
                            $prep_instructions = [];
                            if (! is_null($prepInstructions)) {
                                foreach ($prepInstructions as $prepInstruction) {
                                    $prep_instructions[] = $prepInstruction->toString();
                                }

                            }

                            $amazonPrepFeesDetails = $skuPrepInstruction->getAmazonPrepFeesDetailsList();
                            $amazon_prep_fees_details = [];
                            if (! is_null($amazonPrepFeesDetails)) {
                                foreach ($amazonPrepFeesDetails as $amazonPrepFeesDetail) {
                                    $amount = $amazonPrepFeesDetail->getFeePerUnit();

                                    $amazon_prep_fees_details[] = [
                                        'prep_instruction' => $amazonPrepFeesDetail->getPrepInstruction()?->toString() ?? '',
                                        'amount' => $amount?->getValue() ?? 0.00,
                                        'currency' => $amount?->getCurrencyCode()->toString()
                                    ];
                                }
                            }

                            $asinPrepInstruction[] = [
                                'seller_sku' => $skuPrepInstruction->getSellerSku() ?? '',
                                'asin' => $skuPrepInstruction->getAsin() ?? '',
                                'barcode_instruction' => $skuPrepInstruction->getBarcodeInstruction()?->toString() ?? '',
                                'prep_guidance' => $skuPrepInstruction->getPrepGuidance()?->toString() ?? '',
                                'prep_instructions' => $prep_instructions,
                                'amazon_prep_fees_details' => $amazon_prep_fees_details,
                            ];

                        }
                    }
                    var_dump($sku_pre_instructions);

                    $asinPrepInstructions = $getInboundGuidanceResult->getAsinPrepInstructionsList();
                    $asin_prep_instructions = [];
                    if (! is_null($asinPrepInstructions)) {
                        foreach ($asinPrepInstructions as $asinPrepInstruction) {
                            $prepInstructions = $asinPrepInstruction->getPrepInstructionList();
                            $prep_instructions = [];
                            if (! is_null($prepInstructions)) {
                                foreach ($prepInstructions as $prepInstruction) {
                                    $prep_instructions[] = $prepInstruction->toString();
                                }

                            }
                            $asin_prep_instructions[] = [
                                'asin' => $asinPrepInstruction->getAsin() ?? '',
                                'barcode_instruction' => $asinPrepInstruction->getBarcodeInstruction()?->toString() ?? '',
                                'prep_guidance' => $asinPrepInstruction->getPrepGuidance()?->toString() ?? '',
                                'prep_instructions' => $prep_instructions,
                            ];

                        }
                    }
                    var_dump($asin_prep_instructions);

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
