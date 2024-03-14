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
use App\Model\AmazonAppRegionModel;
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

use function Hyperf\Support\make;

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

            $amazonAppRegionCollections = AmazonAppRegionModel::query()
                ->where('merchant_id', $merchant_id)
                ->where('merchant_store_id', $merchant_store_id)
                ->get();
            if ($amazonAppRegionCollections->isEmpty()) {
                return false;
            }
            foreach ($amazonAppRegionCollections as $amazonAppRegionCollection) {
                $amazonAppCollection->setAttribute('region', $amazonAppRegionCollection->region);
                $amazonAppCollection->setAttribute('country_ids', $amazonAppRegionCollection->country_codes);
                $amazonAppCollection->setAttribute('refresh_token', $amazonAppRegionCollection->refresh_token);
                //                $amazonAppCollection->region = $amazonAppRegionCollection->region;
                //                $amazonAppCollection->country_ids = $amazonAppRegionCollection->country_codes;
                //                $amazonAppCollection->refresh_token = $amazonAppRegionCollection->refresh_token;

                /**
                 * @var AmazonSDK $amazonSDK
                 */
                $amazonSDK = make(AmazonSDK::class, [$amazonAppCollection]);

                $region = $amazonSDK->getRegion();

                $retry_sdk = 3;
                while ($retry_sdk) {
                    try {
                        $sdk = $amazonSDK->getSdk($region, true);
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
            }
            return true;
        });
    }
}
