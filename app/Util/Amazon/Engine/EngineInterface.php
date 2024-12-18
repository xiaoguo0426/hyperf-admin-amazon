<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Util\Amazon\Engine;

use App\Util\Amazon\Creator\CreatorInterface;

interface EngineInterface
{
    public function launch(CreatorInterface $creator): bool;
}
