<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Util\RedisHash;

use App\Util\Prefix;

class AmazonReportMarkCanceledHash extends AbstractRedisHash
{
    public function __construct(int $merchant_id, int $merchant_store_id)
    {
        $this->name = Prefix::amazonReportMarkCanceled($merchant_id, $merchant_store_id);
        parent::__construct();
    }

    /**
     * @throws \JsonException
     * @throws \RedisException
     */
    public function mark(string $report_type, array $marketplace_ids, ?\DateTimeInterface $dataStartTime, ?\DateTimeInterface $dataEndTime): bool
    {
        $data_start_time = is_null($dataStartTime) ? '' : $dataStartTime->format('YmdHis');
        $data_end_time = is_null($dataEndTime) ? '' : $dataEndTime->format('YmdHis');

        $hash_key = sprintf('type:%s-marketplace_ids:%s-time:%s+%s', $report_type, implode(',', $marketplace_ids), $data_start_time, $data_end_time);

        $set = $this->setAttr($hash_key, 1);
        $this->ttl(strtotime(date('Y-m-d 23:59:59')) - time() + 7200); // 设置key有效期

        return $set;
    }

    /**
     * @throws \JsonException
     * @throws \RedisException
     */
    public function check(string $report_type, array $marketplace_ids, ?\DateTimeInterface $dataStartTime, ?\DateTimeInterface $dataEndTime): bool
    {
        $data_start_time = is_null($dataStartTime) ? '' : $dataStartTime->format('YmdHis');
        $data_end_time = is_null($dataEndTime) ? '' : $dataEndTime->format('YmdHis');

        $key = sprintf('type:%s-marketplace_ids:%s-time:%s+%s', $report_type, implode(',', $marketplace_ids), $data_start_time, $data_end_time);
        return $this->getAttr($key) === 1;
    }
}
