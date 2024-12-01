<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Command\Crontab\Amazon;

use App\Model\AmazonAppModel;
use App\Util\AmazonApp;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;

#[Command]
class CommonCacheRefresh extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('crontab:amazon:common-cache-refresh');
        // 指令配置
        $this->setDescription('Crontab Amazon Common Cache Refresh Command');
    }

    public function handle(): void
    {
        AmazonApp::single(static function (AmazonAppModel $amazonAppCollection) {
            $merchant_id = $amazonAppCollection->merchant_id;
            $merchant_store_id = $amazonAppCollection->merchant_store_id;

            return true;
        });
    }
}
