<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Util\Amazon\Creator;

class ListPlacementOptionsCreator implements CreatorInterface
{
    private string $inbound_plan_id;

    public function getInboundPlanId(): string
    {
        return $this->inbound_plan_id;
    }

    public function setInboundPlanId(string $inbound_plan_id): void
    {
        $this->inbound_plan_id = $inbound_plan_id;
    }
}
