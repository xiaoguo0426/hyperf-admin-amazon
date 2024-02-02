<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Command\Amazon\ProductFees;

use AmazonPHP\SellingPartner\AccessToken;
use AmazonPHP\SellingPartner\Exception\ApiException;
use AmazonPHP\SellingPartner\Exception\InvalidArgumentException;
use AmazonPHP\SellingPartner\Marketplace;
use AmazonPHP\SellingPartner\Model\ProductFees\FeesEstimateRequest;
use AmazonPHP\SellingPartner\Model\ProductFees\GetMyFeesEstimateRequest;
use AmazonPHP\SellingPartner\Model\ProductFees\MoneyType;
use AmazonPHP\SellingPartner\Model\ProductFees\Points;
use AmazonPHP\SellingPartner\Model\ProductFees\PriceToEstimateFees;
use AmazonPHP\SellingPartner\Regions;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use App\Util\AmazonApp;
use App\Util\AmazonSDK;
use App\Util\Log\AmazonOrdersLog;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\StdoutLoggerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use function Hyperf\Support\make;

#[Command]
class GetMyFeesEstimateForSKU extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('amazon:product-fees:get-my-fees-estimate-for-sku');
    }

    public function configure(): void
    {
        parent::configure();
        // 指令配置
        $this->addArgument('merchant_id', InputArgument::REQUIRED, '商户id')
            ->addArgument('merchant_store_id', InputArgument::REQUIRED, '店铺id')
            ->addArgument('region', InputArgument::REQUIRED, '地区')
            ->addArgument('seller_sku', InputArgument::REQUIRED, 'Seller SKU')
            ->addOption('marketplace_id', null, InputOption::VALUE_OPTIONAL, '市场ID', null)
            ->addOption('is_amazon_fulfilled', null, InputOption::VALUE_OPTIONAL, '该报价是否由亚马逊履行 true/false', null)
            ->addOption('price_to_estimate_fees', null, InputOption::VALUE_OPTIONAL, '费用估算所依据的产品价格', null)
//            ->addOption('identifier', null, InputOption::VALUE_OPTIONAL, '市场ID', null)
            ->addOption('optional_fulfillment_program', null, InputOption::VALUE_OPTIONAL, '一个可选的注册计划，用于在亚马逊履行优惠时返回估计费用（--is_amazon_fulfilled选项设置为true）FBA_CORE/FBA_SNL/FBA_EFN', null)
            ->setDescription('Amazon Product Fees API GetMyFeesEstimateForSKU Command');
    }

    public function handle(): void
    {
        $merchant_id = (int) $this->input->getArgument('merchant_id');
        $merchant_store_id = (int) $this->input->getArgument('merchant_store_id');
        $region = $this->input->getArgument('region');
        $seller_sku = $this->input->getArgument('seller_sku');

        $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);

        if (! Regions::isValid($region)) {
            $console->error('region参数不正确');
            return;
        }

        $that = $this;

        AmazonApp::tok2($merchant_id, $merchant_store_id, $region, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) use ($that, $seller_sku) {
            $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
            $logger = ApplicationContext::getContainer()->get(AmazonOrdersLog::class);

            if (Regions::EUROPE === $region) {
                $country_codes = [
                    Marketplace::ES()->countryCode(),
                    Marketplace::GB()->countryCode(),
                    Marketplace::FR()->countryCode(),
                    Marketplace::BE()->countryCode(),
                    Marketplace::NL()->countryCode(),
                    Marketplace::DE()->countryCode(),
                    Marketplace::IT()->countryCode(),
                    Marketplace::SE()->countryCode(),
                    Marketplace::PL()->countryCode(),
                    Marketplace::EG()->countryCode(),
                    Marketplace::TR()->countryCode(),
                    Marketplace::SA()->countryCode(),
                    Marketplace::AE()->countryCode(),
                    Marketplace::IN()->countryCode(),
                ];
                $default_country_code = 'GB';
            } else if (Regions::NORTH_AMERICA === $region) {
                $country_codes = [
                    Marketplace::CA()->countryCode(),
                    Marketplace::US()->countryCode(),
                    Marketplace::MX()->countryCode(),
                ];
                $default_country_code = 'US';
            } else {
                $country_codes = [
                    Marketplace::SG()->countryCode(),
                    Marketplace::AU()->countryCode(),
                    Marketplace::JP()->countryCode(),
                ];
                $default_country_code = 'SG';
            }

            $helper = $that->getHelper('question');
            $input = $that->input;
            $output = $that->output;

            $country_code = $helper->ask($input, $output, new ChoiceQuestion(
                sprintf('请选择市场. 默认%s', $default_country_code),
                $country_codes,
                $default_country_code
            ));
            $marketplace_id = Marketplace::fromCountry($country_code)->id();

            $is_amazon_fulfilled = $helper->ask($input, $output, new ChoiceQuestion(
                '报价是否有亚马逊履行. 默认true',
                // choices can also be PHP objects that implement __toString() method
                ['true', 'false'],
                0
            ));

            $listing_price_amount = $helper->ask($input, $output, new Question(
                '费用估算所依据的产品价格 -- 价格 >> '
            ));
            $listing_price_amount_currency_code = $helper->ask($input, $output, new Question(
                '费用估算所依据的产品价格 -- 价格的货币 默认USD >> ', 'USD'
            ));

            $shipping_code_amount = $helper->ask($input, $output, new Question(
                '费用估算所依据的产品价格 -- 运费 >> '
            ));
            $shipping_code_amount_currency_code = $helper->ask($input, $output, new Question(
                '费用估算所依据的产品价格 -- 运费的货币 默认USD >> ', 'USD'
            ));

            $points_number = $helper->ask($input, $output, new Question(
                '购买商品时提供的亚马逊积分数 -- 积分 >> ', 0
            ));
            $points_monetary_value_amount = $helper->ask($input, $output, new Question(
                '购买商品时提供的亚马逊积分数 -- 积分价值 >> ', 0
            ));
            $points_monetary_value_amount_currency_code = $helper->ask($input, $output, new Question(
                '购买商品时提供的亚马逊积分数 -- 积分价值的货币 默认USD >> ', 'USD'
            ));

            $retry = 30;
            //https://developer-docs.amazon.com/sp-api/docs/product-fees-api-v0-reference#getmyfeesestimateforsku
            while (true) {
                try {

                    $body = new GetMyFeesEstimateRequest();
                    $feesEstimateRequest = new FeesEstimateRequest();

                    $identifier = time() . random_int(1000, 9999);

                    $feesEstimateRequest->setMarketplaceId($marketplace_id);

                    $feesEstimateRequest->setIsAmazonFulfilled($is_amazon_fulfilled === 'true');

                    $price_to_estimate_fees = new PriceToEstimateFees();

                    $listing_price = new MoneyType();
                    $listing_price->setAmount((float) $listing_price_amount);
                    $listing_price->setCurrencyCode($listing_price_amount_currency_code);
                    $price_to_estimate_fees->setListingPrice($listing_price);

                    $shipping = new MoneyType();
                    $shipping->setAmount((float) $shipping_code_amount);
                    $shipping->setCurrencyCode($shipping_code_amount_currency_code);
                    $price_to_estimate_fees->setShipping($shipping);

                    $points = new Points();
                    $points->setPointsNumber((int) $points_number);

                    $points_monetary_value = new MoneyType();
                    $points_monetary_value->setAmount((float) $points_monetary_value_amount);
                    $points_monetary_value->setCurrencyCode($points_monetary_value_amount_currency_code);

                    $points->setPointsMonetaryValue($points_monetary_value);

                    $price_to_estimate_fees->setPoints($points);

                    $feesEstimateRequest->setPriceToEstimateFees($price_to_estimate_fees);
                    $feesEstimateRequest->setIdentifier($identifier);
//                    $feesEstimateRequest->setOptionalFulfillmentProgram();

                    $body->setFeesEstimateRequest($feesEstimateRequest);

                    $response = $sdk->productFees()->getMyFeesEstimateForSKU($accessToken, $region, $seller_sku, $body);

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
                    $fees_estimate_result = $payload->getFeesEstimateResult();
                    var_dump($fees_estimate_result);

                    break;
                } catch (ApiException $e) {
                    if (! is_null($e->getResponseBody())) {
                        $body = json_decode($e->getResponseBody(), true, 512, JSON_THROW_ON_ERROR);
                        if (isset($body['errors'])) {
                            $errors = $body['errors'];
                            foreach ($errors as $error) {
                                if ($error['code'] !== 'QuotaExceeded') {
                                    $console->warning(sprintf('merchant_id:%s merchant_store_id:%s region:%s Page:%s code:%s message:%s', $merchant_id, $merchant_store_id, $region, $page, $error['code'], $error['message']));
                                    break 2;
                                }
                            }
                        }
                    }

                    --$retry;
                    if ($retry > 0) {
                        $console->warning(sprintf('merchant_id:%s merchant_store_id:%s region:%s Page:%s 第 % s 次重试', $merchant_id, $merchant_store_id, $region, $page, $retry));
                        sleep(3);
                        continue;
                    }

                    $console->error(sprintf('merchant_id:%s merchant_store_id:%s region:%s Page:%s 重试次数已用完', $merchant_id, $merchant_store_id, $region, $page));
                    break;
                } catch (InvalidArgumentException $e) {
                    $console->error(sprintf('merchant_id:%s merchant_store_id:%s region:%s InvalidArgumentException % s % s', $merchant_id, $merchant_store_id, $region, $e->getCode(), $e->getMessage()));
                    break;
                }
            }


            return true;
        });
    }
}
