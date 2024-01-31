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
use AmazonPHP\SellingPartner\Model\Orders\PreferredDeliveryTime;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use App\Util\AmazonApp;
use App\Util\AmazonSDK;
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

#[Command]
class GetOrderAddress extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('amazon:order:get-order-address');
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
        $amazon_order_id = $this->input->getArgument('order_id');

        $that = $this;

        AmazonApp::tok($merchant_id, $merchant_store_id, static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) use ($amazon_order_id) {
            $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);

            $retry = 30;
            while (true) {
                try {
                    $response = $sdk->orders()->getOrderAddress($accessToken, $region, $amazon_order_id);
                    $orderAddress = $response->getPayload();
                    if (is_null($orderAddress)) {
                        break;
                    }

                    $amazon_order_id = $orderAddress->getAmazonOrderId();
                    $buyer_company_name = $orderAddress->getBuyerCompanyName() ?? '';
                    $deliveryPreferences = $orderAddress->getDeliveryPreferences();
                    if (! is_null($deliveryPreferences)) {
                        $preferredDeliveryTime = $deliveryPreferences->getPreferredDeliveryTime();
                        if (! is_null($preferredDeliveryTime)) {
                            $businessHours = $preferredDeliveryTime->getBusinessHours();
                            if (! is_null($businessHours)) {
                                foreach ($businessHours as $businessHour) {
                                    $businessHour->getDayOfWeekAllowableValues();
                                    $businessHour->getDayOfWeek() ?? '';
                                    $businessHour->getOpenIntervals();
                                }
                            }
                            $preferredDeliveryTime->getExceptionDates();
                        }


                        $address_instructions = $deliveryPreferences->getAddressInstructions() ?? '';
                        $drop_off_location = $deliveryPreferences->getDropOffLocation() ?? '';
                        $otherDeliveryAttributes = $deliveryPreferences->getOtherAttributes();
                        $other_delivery_attributes = [];
                        if (! is_null($otherDeliveryAttributes)) {
                            foreach ($otherDeliveryAttributes as $otherDeliveryAttribute) {
                                $other_delivery_attributes[] = $otherDeliveryAttribute->toString();
                            }

                        }
                    }


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
