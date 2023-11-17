<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Command\Amazon\Order;

use AmazonPHP\SellingPartner\AccessToken;
use AmazonPHP\SellingPartner\Exception\ApiException;
use AmazonPHP\SellingPartner\Exception\InvalidArgumentException;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use App\Util\AmazonApp;
use App\Util\AmazonSDK;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\StdoutLoggerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;

#[Command]
class GetOrderBuyerInfo extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('amazon:order:get-order-buyer-info');
    }

    public function configure(): void
    {
        parent::configure();
        // 指令配置
        $this->addArgument('merchant_id', InputArgument::REQUIRED, '商户id')
            ->addArgument('merchant_store_id', InputArgument::REQUIRED, '店铺id')
            ->addArgument('order_id', InputArgument::REQUIRED, 'order_id')
            ->setDescription('Amazon Order API Buyer Info Command');
    }

    public function handle(): void
    {
        $merchant_id = (int) $this->input->getArgument('merchant_id');
        $merchant_store_id = (int) $this->input->getArgument('merchant_store_id');
        $amazon_order_id = $this->input->getArgument('order_id');

        $that = $this;

        AmazonApp::tok($merchant_id, $merchant_store_id, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) use ($amazon_order_id) {
            $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);

            $retry = 30;
            while (true) {
                try {
                    $response = $sdk->orders()->getOrderBuyerInfo($accessToken, $region, $amazon_order_id);

                    $orderBuyerInfo = $response->getPayload();
                    if (is_null($orderBuyerInfo)) {
                        break;
                    }

                    $amazon_order_id = $orderBuyerInfo->getAmazonOrderId();
                    $email = $orderBuyerInfo->getBuyerEmail();
                    $name = $orderBuyerInfo->getBuyerName();
                    $country = $orderBuyerInfo->getBuyerCounty();
                    $taxInfo = $orderBuyerInfo->getBuyerTaxInfo();
                    $purchase_order_number = $orderBuyerInfo->getPurchaseOrderNumber();
                    var_dump($amazon_order_id);
                    var_dump($email);
                    var_dump($name);
                    var_dump($country);
                    if (! is_null($taxInfo)) {
                        var_dump($taxInfo->getCompanyLegalName());
                        var_dump($taxInfo->getTaxingRegion());
                        $taxInfo->getTaxClassifications();
                    }
                    var_dump($purchase_order_number);

                    break;
                } catch (ApiException $e) {
                    if (! is_null($e->getResponseBody())) {
                        $body = json_decode($e->getResponseBody(), true, 512, JSON_THROW_ON_ERROR);
                        if (isset($body['errors'])) {
                            $errors = $body['errors'];
                            foreach ($errors as $error) {
                                if ($error['code'] !== 'QuotaExceeded') {
                                    $console->warning(sprintf('merchant_id:%s merchant_store_id:%s code:%s message:%s', $merchant_id, $merchant_store_id, $error['code'], $error['message']));
                                    break 2;
                                }
                            }
                        }
                    }

                    --$retry;
                    if ($retry > 0) {
                        $console->warning(sprintf('merchant_id:%s merchant_store_id:%s 第 %s 次重试', $merchant_id, $merchant_store_id, $retry));
                        sleep(3);
                        continue;
                    }

                    $console->error(sprintf('merchant_id:%s merchant_store_id:%s 重试次数已用完', $merchant_id, $merchant_store_id));
                    break;
                } catch (InvalidArgumentException $e) {
                    $console->error(sprintf('merchant_id:%s merchant_store_id:%s InvalidArgumentException %s %s', $merchant_id, $merchant_store_id, $e->getCode(), $e->getMessage()));
                    break;
                }
            }

            return true;
        });
    }
}
