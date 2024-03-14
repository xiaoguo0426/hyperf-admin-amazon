<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Util;

use Hyperf\Contract\StdoutLoggerInterface;
use Stringable;

use function Hyperf\Support\env;

/**
 * Class ConsoleLog.
 *
 * @method void emergency(string|Stringable $message, array $context = [])
 * @method void alert(string|Stringable $message, array $context = [])
 * @method void critical(string|Stringable $message, array $context = [])
 * @method void error(string|Stringable $message, array $context = [])
 * @method void warning(string|Stringable $message, array $context = [])
 * @method void notice(string|Stringable $message, array $context = [])
 * @method void info(string|Stringable $message, array $context = [])
 * @method void debug(string|Stringable $message, array $context = [])
 */
class ConsoleLog
{
    protected StdoutLoggerInterface $logger;

    public function __construct(StdoutLoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function __call($name, $arguments)
    {
        return env('DEBUG') !== true ? $this->logger->{$name}(...$arguments) : null;
    }

    public function newLine(): void
    {
        echo "\r\n";
    }
}
