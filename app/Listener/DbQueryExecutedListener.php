<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Listener;

use App\Util\Log\SqlLog;
use Hyperf\Collection\Arr;
use Hyperf\Database\Events\QueryExecuted;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Psr\Container\ContainerInterface;

#[Listener]
class DbQueryExecutedListener implements ListenerInterface
{
    private SqlLog $logger;

    public function __construct(ContainerInterface $container)
    {
        $this->logger = $container->get(SqlLog::class);
    }

    public function listen(): array
    {
        return [
            QueryExecuted::class,
        ];
    }

    public function process(object $event): void
    {
        if ($event instanceof QueryExecuted) {
            $sql = $event->sql;
            if (! Arr::isAssoc($event->bindings)) {
                $position = 0;
                foreach ($event->bindings as $value) {
                    $position = strpos($sql, '?', $position);
                    if ($position === false) {
                        break;
                    }
                    if (is_numeric($value)) {
                        $val = (string) $value;
                    } elseif (is_string($value)) {
                        $val = "'{$value}'";
                    } else {
                        $val = "'{$value}'";
                        $this->logger->notice(sprintf('sql:%s 中参数位置 %s 值类型有误，请检查.', $sql, $position));
                    }
                    //                    $val = "'{$value}'";
                    $sql = substr_replace($sql, $val, $position, 1);
                    $position += strlen($val);
                }
            }

            $this->logger->info(sprintf('[%s ms] %s', $event->time, $sql));
        }
    }
}
