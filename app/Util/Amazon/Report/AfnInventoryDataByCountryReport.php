<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Util\Amazon\Report;

use AmazonPHP\SellingPartner\Exception\InvalidArgumentException;
use AmazonPHP\SellingPartner\Regions;
use App\Util\Amazon\Report\Runner\ReportRunnerInterface;

class AfnInventoryDataByCountryReport extends ReportBase
{
    public function run(ReportRunnerInterface $reportRunner): bool
    {
        // TODO: Implement run() method.
        return true;
    }

    /**
     * 请求报告
     * @throws InvalidArgumentException
     */
    public function requestReport(array $marketplace_ids, callable $func): void
    {
        //Only For EU
        if ($this->region !== Regions::EUROPE) {
            return;
        }
        parent::requestReport($marketplace_ids, $func);
    }

}
