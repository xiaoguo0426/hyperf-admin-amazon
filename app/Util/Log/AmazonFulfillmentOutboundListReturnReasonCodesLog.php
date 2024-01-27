<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Util\Log;

class AmazonFulfillmentOutboundListReturnReasonCodesLog extends AbstractLog
{
    public function __construct()
    {
        parent::__construct('list-return-reason-codes', 'amazon-fulfillment-outbound');
    }
}
