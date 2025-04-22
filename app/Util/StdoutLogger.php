<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Util;

use Carbon\Carbon;
use DateTime;
use Psr\Log\LogLevel;

/**
 * Default logger for logging server start and requests.
 * PSR-3 logger implementation that logs to STDOUT, using a newline after each
 * message. Priority is ignored.
 */
final class StdoutLogger extends \Hyperf\Framework\Logger\StdoutLogger
{
    protected function getMessage(string $message, string $level = LogLevel::INFO, array $tags = []): string
    {
        $tag = match ($level) {
            LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL => 'error',
            LogLevel::ERROR => 'fg=red',
            LogLevel::WARNING, LogLevel::NOTICE => 'comment',
            default => 'info',
        };

        $datetime = (new DateTime())->format('Y-m-d H:i:s-v');
//        $datetime = Carbon::now()->format('Y-m-d H:i:s-v');

        $template = \sprintf('[%s] <%s>[%s]</>', $datetime, $tag, strtoupper($level));

        $implodedTags = '';
        foreach ($tags as $value) {
            $implodedTags .= (' [' . $value . ']');
        }

        return \sprintf($template . ' %s' . $implodedTags, $message);
    }
}
