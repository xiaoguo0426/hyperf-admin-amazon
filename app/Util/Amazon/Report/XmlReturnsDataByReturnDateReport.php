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

class XmlReturnsDataByReturnDateReport extends ReportBase
{
    /**
     * @param ReportRunnerInterface $reportRunner
     * @return bool
     */
    public function run(ReportRunnerInterface $reportRunner): bool
    {
        // TODO: Implement run() method.
        return true;
    }
}
