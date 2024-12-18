<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Util\Amazon\Creator;

use Carbon\Carbon;

class ScheduleReportCreator implements CreatorInterface
{
    private string $report_type;

    private ?Carbon $start_date;

    private ?Carbon $end_date;

    private bool $is_range_date = false;

    private bool $is_force_create = false;

    public function __construct(string $report_type, ?Carbon $start_date, ?Carbon $end_date, bool $is_range_date, bool $is_force_create)
    {
        $this->report_type = $report_type;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->is_range_date = $is_range_date;
        $this->is_force_create = $is_force_create;
    }

    public function getReportType(): string
    {
        return $this->report_type;
    }

    public function getStartDate(): Carbon
    {
        return $this->start_date;
    }

    public function getEndDate(): Carbon
    {
        return $this->end_date;
    }

    public function getIsRangeDate(): bool
    {
        return $this->is_range_date;
    }

    public function getIsForceCreate(): bool
    {
        return $this->is_force_create;
    }
}
