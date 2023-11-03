<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Command\Crontab\Amazon;

use AmazonPHP\SellingPartner\Exception\ApiException;
use App\Model\AmazonAppModel;
use App\Util\AmazonApp;
use App\Util\AmazonSDK;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\StdoutLoggerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Client\ClientExceptionInterface;

#[Command]
class RefreshAppToken extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('crontab:amazon:refresh-app-token');
    }

    public function configure(): void
    {
        parent::configure();
        // 指令配置
        $this->setDescription('Crontab Amazon Refresh App Token Command');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function handle(): void
    {
        AmazonApp::single(static function (AmazonAppModel $amazonAppCollection) {
            $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);

            $merchant_id = $amazonAppCollection->merchant_id;
            $merchant_store_id = $amazonAppCollection->merchant_store_id;
            $seller_id = $amazonAppCollection->seller_id;
            $region = $amazonAppCollection->region;

            $amazonSDK = new AmazonSDK($amazonAppCollection);

            $retry_sdk = 3;
            while ($retry_sdk) {
                try {
                    $sdk = $amazonSDK->getSdk(true);
                    break;
                } catch (ApiException|ClientExceptionInterface|\Exception $exception) {
                    --$retry_sdk;
                    if ($retry_sdk === 0) {
                        $log = sprintf('AmazonAppRefreshToken Amazon App SDK构建失败，请检查. %s merchant_id:%s merchant_store_id:%s ', $exception->getMessage(), $merchant_id, $merchant_store_id);
                        $console->error($log);
                        return false;
                    }
                    continue;
                }
            }

            $retry_token = 3;
            while ($retry_token) {
                try {
                    $accessToken = $amazonSDK->getToken($region, true);
                    break;
                } catch (ApiException|ClientExceptionInterface|\Exception $exception) {
                    --$retry_token;
                    if ($retry_token === 0) {
                        $log = sprintf('AmazonAppRefreshToken Amazon App Token获取失败，请检查. %s merchant_id:%s merchant_store_id:%s ', $exception->getMessage(), $merchant_id, $merchant_store_id);
                        $console->error($log);
                        return false;
                    }
                    continue;
                }
            }

            return true;
        });
    }
}
