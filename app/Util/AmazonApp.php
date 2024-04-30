<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Util;

use AmazonPHP\SellingPartner\Exception\ApiException;
use AmazonPHP\SellingPartner\Regions;
use App\Exception\AmazonAppException;
use App\Exception\BusinessException;
use App\Model\AmazonAppModel;
use App\Model\AmazonAppRegionModel;
use App\Util\RedisHash\AmazonAppHash;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Database\Model\ModelNotFoundException;
use Hyperf\Di\Exception\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Client\ClientExceptionInterface;

use function Hyperf\Support\make;

class AmazonApp
{
    /**
     * 单个Amazon应用配置回调.
     */
    public static function tick(int $merchant_id, int $merchant_store_id, callable $func): bool
    {
        if (! is_callable($func)) {
            return true; // 直接终止处理
        }
        $appHash = make(AmazonAppHash::class, ['merchant_id' => $merchant_id, 'merchant_store_id' => $merchant_store_id]);
        $id = $appHash->id;

        if ($id) {
            $status = $appHash->status;

            if ($appHash->status !== Constants::STATUS_ACTIVE) {
                return true;
            }

            $amazonAppCollection = new AmazonAppModel();
            $amazonAppCollection->id = $id;
            $amazonAppCollection->merchant_id = $appHash->merchant_id;
            $amazonAppCollection->merchant_store_id = $appHash->merchant_store_id;
            $amazonAppCollection->seller_id = $appHash->seller_id;
            $amazonAppCollection->app_id = $appHash->app_id;
            $amazonAppCollection->app_name = $appHash->app_name;
            $amazonAppCollection->aws_access_key = $appHash->aws_access_key;
            $amazonAppCollection->aws_secret_key = $appHash->aws_secret_key;
            $amazonAppCollection->user_arn = $appHash->user_arn;
            $amazonAppCollection->role_arn = $appHash->role_arn;
            $amazonAppCollection->lwa_client_id = $appHash->lwa_client_id;
            $amazonAppCollection->lwa_client_id_secret = $appHash->lwa_client_id_secret;
            $amazonAppCollection->status = $status;
        } else {
            // 缓存不存在
            try {
                $amazonAppCollection = AmazonAppModel::query()->where('merchant_id', $merchant_id)->where('merchant_store_id', $merchant_store_id)->firstOrFail();
            } catch (ModelNotFoundException $exception) {
                throw new AmazonAppException(sprintf('AmazonAppException ModelNotFound. %s', $exception->getMessage()), 201);
            }
            $appHash->load($amazonAppCollection->toArray());
            $appHash->ttl(3600);
        }

        return $func($amazonAppCollection);
    }

    /**
     * 单个Amazon应用配置回调并触发Amazon SDK(指定Amazon应用+应用的所有地区).
     * @throws NotFoundException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \RedisException
     */
    public static function tok(int $merchant_id, int $merchant_store_id, callable $func): bool
    {
        return self::tick($merchant_id, $merchant_store_id, static function (AmazonAppModel $amazonAppModel) use ($func) {
            if (! is_callable($func)) {
                return false;
            }

            $merchant_id = $amazonAppModel->merchant_id;
            $merchant_store_id = $amazonAppModel->merchant_store_id;

            $amazonAppRegionCollections = AmazonAppRegionModel::query()
                ->where('merchant_id', $merchant_id)
                ->where('merchant_store_id', $merchant_store_id)
                ->get();
            if ($amazonAppRegionCollections->isEmpty()) {
                return false;
            }
            foreach ($amazonAppRegionCollections as $amazonAppRegionCollection) {
                $amazonAppModel->setAttribute('region', $amazonAppRegionCollection->region);
                $amazonAppModel->setAttribute('country_ids', $amazonAppRegionCollection->country_codes);
                $amazonAppModel->setAttribute('refresh_token', $amazonAppRegionCollection->refresh_token);

                /**
                 * @var AmazonSDK $amazonSDK
                 */
                $amazonSDK = make(AmazonSDK::class, [$amazonAppModel]);

                $region = $amazonSDK->getRegion();

                try {
                    $sdk = $amazonSDK->getSdk($region);
                } catch (ApiException|ClientExceptionInterface|\JsonException $exception) {
                    $log = sprintf('Amazon App SDK构建失败，请检查. %s merchant_id:%s merchant_store_id:%s ', $exception->getMessage(), $merchant_id, $merchant_store_id);
                    $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
                    $console->error($log);
                    throw new AmazonAppException($log, 201);
                }

                try {
                    $accessToken = $amazonSDK->getToken($region);
                } catch (ApiException|ClientExceptionInterface $exception) {
                    $log = sprintf('Amazon App Token获取失败，请检查. %s merchant_id:%s merchant_store_id:%s ', $exception->getMessage(), $merchant_id, $merchant_store_id);
                    $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
                    $console->error($log);
                    throw new AmazonAppException($log, 201);
                }

                $marketplace_ids = $amazonSDK->getMarketplaceIds();

                $func($amazonSDK, $merchant_id, $merchant_store_id, $sdk, $accessToken, $region, $marketplace_ids);
            }

            return true;
        });
    }

    /**
     * 单个Amazon应用配置回调并触发Amazon SDK(指定Amazon应用+应用指定地区).
     * @throws NotFoundException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \RedisException
     */
    public static function tok2(int $merchant_id, int $merchant_store_id, string $region, callable $func): bool
    {
        return self::tick($merchant_id, $merchant_store_id, static function (AmazonAppModel $amazonAppModel) use ($func, $region) {
            if (! is_callable($func)) {
                return false;
            }

            $merchant_id = $amazonAppModel->merchant_id;
            $merchant_store_id = $amazonAppModel->merchant_store_id;

            // 验证region知否为合法
            try {
                self::region($region);
            } catch (BusinessException $businessException) {
                $log = sprintf('Amazon App SDK构建失败，region值非法. region:%s ', $region);
                $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
                $console->error($log);
                return true;
            }

            try {
                $amazonAppRegionCollection = AmazonAppRegionModel::query()
                    ->where('merchant_id', $merchant_id)
                    ->where('merchant_store_id', $merchant_store_id)
                    ->where('region', $region)
                    ->firstOrFail();
            } catch (ModelNotFoundException $modelNotFoundException) {
                $log = sprintf('Amazon App SDK构建失败，AmazonAppRegion查询失败. %s merchant_id:%s merchant_store_id:%s region:%s ', $modelNotFoundException->getMessage(), $merchant_id, $merchant_store_id, $region);
                $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
                $console->error($log);
                return true;
            }

            $amazonAppModel->setAttribute('region', $amazonAppRegionCollection->region);
            $amazonAppModel->setAttribute('country_ids', $amazonAppRegionCollection->country_codes);
            $amazonAppModel->setAttribute('refresh_token', $amazonAppRegionCollection->refresh_token);

            /**
             * @var AmazonSDK $amazonSDK
             */
            $amazonSDK = make(AmazonSDK::class, [$amazonAppModel]);

            $region = $amazonSDK->getRegion();

            try {
                $sdk = $amazonSDK->getSdk($region);
            } catch (ApiException|ClientExceptionInterface|\JsonException $exception) {
                $log = sprintf('Amazon App SDK构建失败，请检查. %s merchant_id:%s merchant_store_id:%s ', $exception->getMessage(), $merchant_id, $merchant_store_id);
                $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
                $console->error($log);
                throw new AmazonAppException($log, 201);
            }

            try {
                $accessToken = $amazonSDK->getToken($region);
            } catch (ApiException|ClientExceptionInterface $exception) {
                $log = sprintf('Amazon App Token获取失败，请检查. %s merchant_id:%s merchant_store_id:%s ', $exception->getMessage(), $merchant_id, $merchant_store_id);
                $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
                $console->error($log);
                throw new AmazonAppException($log, 201);
            }

            $marketplace_ids = $amazonSDK->getMarketplaceIds();

            $func($amazonSDK, $merchant_id, $merchant_store_id, $sdk, $accessToken, $region, $marketplace_ids);

            return true;
        });
    }

    /**
     * 所有Amazon应用配置回调.
     */
    public static function single(callable $func): bool
    {
        $amazonAppCollections = AmazonAppModel::query()->where('status', Constants::STATUS_ACTIVE)->get();
        if ($amazonAppCollections->isEmpty()) {
            return false;
        }

        foreach ($amazonAppCollections as $amazonAppCollection) {
            $func($amazonAppCollection);
        }

        return true;
    }

    /**
     * 所有Amazon应用配置回调并触发Amazon SDK(所有Amazon应用+应用的所有地区).
     * @throws ContainerExceptionInterface
     * @throws NotFoundException
     * @throws NotFoundExceptionInterface
     * @throws \RedisException
     */
    public static function each(callable $func): void
    {
        self::single(static function (AmazonAppModel $amazonAppCollection) use ($func) {
            if (! is_callable($func)) {
                return false;
            }

            $merchant_id = $amazonAppCollection->merchant_id;
            $merchant_store_id = $amazonAppCollection->merchant_store_id;

            return self::tok($merchant_id, $merchant_store_id, $func);
        });
    }

    /**
     * @param string[] $regions
     */
    public static function regions(array $regions): void
    {
        foreach ($regions as $region) {
            self::region($region);
        }
    }

    public static function region(string $region): void
    {
        if (! Regions::isValid($region)) {
            throw new BusinessException('Invalid Region', 1);
        }
    }
}
