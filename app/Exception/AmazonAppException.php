<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Exception;

class AmazonAppException extends BaseException
{
    public function __construct($message = 'Amazon App Exception', $code = 200, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
