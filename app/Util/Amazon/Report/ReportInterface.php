<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Util\Amazon\Report;

use App\Util\Amazon\Report\Runner\ReportRunnerInterface;

interface ReportInterface
{
    /**
     * 处理报告内容.
     */
    public function run(ReportRunnerInterface $reportRunner): bool;
}
