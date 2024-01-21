<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Command\Amazon\FulfillmentInbound;

use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;

#[Command]
class GetShipmentItems extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('amazon:fulfillment-inbound:get-shipment-items');
    }

    public function configure(): void
    {
        parent::configure();
        $this->setDescription('Amazon Fulfillment Inbound Get Shipment Items Command');
    }

    public function handle(): void {}
}
