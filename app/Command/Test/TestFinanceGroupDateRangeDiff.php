<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Command\Test;

use AmazonPHP\SellingPartner\AccessToken;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use App\Model\AmazonFinancialGroupModel;
use App\Util\AmazonApp;
use App\Util\AmazonSDK;
use Carbon\Carbon;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\StdoutLoggerInterface;
use Psr\Container\ContainerInterface;

#[Command]
class TestFinanceGroupDateRangeDiff extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('test:finance-group-date-range-diff');
    }

    public function handle(): void
    {
        AmazonApp::each(static function (AmazonSDK $amazonSDK, int $merchant_id, int $merchant_store_id, SellingPartnerSDK $sdk, AccessToken $accessToken, string $region, array $marketplace_ids) {
            $console = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
            $console->info(sprintf('merchant_id:%s merchant_store_id:%s region:%s', $merchant_id, $merchant_store_id, $region));
            // 查询店铺下不同的region的处于Closed状态的财务事件组的开始时间和结束时间的差值
            $collections = AmazonFinancialGroupModel::query()->where('merchant_id', $merchant_id)
                ->where('merchant_store_id', $merchant_store_id)
                ->where('region', $region)
                ->where('processing_status', 'Closed')
                ->get();

            $max_diff_days = 0;
            $max_diff_day_group_id = '';
            $max_diff_day_top_10 = []; // 最大的相差天数 10条
            $diff_day_group_id_map = [];
            foreach ($collections as $collection) {
                $startDate = Carbon::createFromFormat('Y-m-d H:i:s', $collection->financial_event_group_start);
                $endDate = Carbon::createFromFormat('Y-m-d H:i:s', $collection->financial_event_group_end);

                $diff_days = $endDate->diffInDays($startDate);

                $diff_day_group_id_map[$collection->financial_event_group_id] = $diff_days;
                if ($diff_days > $max_diff_days) {
                    $max_diff_days = $diff_days;
                    $max_diff_day_group_id = $collection->financial_event_group_id;
                }
            }
            var_dump($diff_day_group_id_map);
            $console->info("region: {$region}, max_diff_days: {$max_diff_days}, max_diff_day_group_id: {$max_diff_day_group_id}");
        });
    }
}
