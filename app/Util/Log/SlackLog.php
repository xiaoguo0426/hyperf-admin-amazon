<?php

namespace App\Util\Log;

use Hyperf\Logger\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\MissingExtensionException;
use Monolog\Handler\SlackWebhookHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use function Hyperf\Config\config;

/**
 * Class SlackLog
 * @package extension\Log
 * @method void log(mixed $level, string $message, array $context = [])
 * @method void debug($message, array $context = [])
 * @method void info($message, array $context = [])
 * @method void notice($message, array $context = [])
 * @method void warning($message, array $context = [])
 * @method void error($message, array $context = [])
 * @method void critical($message, array $context = [])
 * @method void alert($message, array $context = [])
 * @method void emergency($message, array $context = [])
 */
class SlackLog
{

    private Logger $logger;

    /**
     * @throws MissingExtensionException
     * @throws MissingExtensionException
     */
    public function __construct()
    {
        $config = config('log.channels.slack');

        $channel = $config['channel'];

        $level = $config['level'] ?? Level::Debug;
        $bubble = $config['bubble'];
        $exclude_fields = $config['exclude_fields'];

        $this->logger = new Logger($config['channel']);

        $dateFormat = "Y-m-d\TH:i:sP";

        $output = "[%datetime%] %channel%.%level_name%: %message% %context% %extra% \n";

        $formatter = new LineFormatter($output, $dateFormat);

        //正式生产线才记录日志到slack
//        if (! app()->isDebug()) {
//            $webhook_url = $config['webhook_url'];
//
//            $username = $config['username'];
//            $use_attachment = $config['use_attachment'];
//            $icon_emoji = $config['icon_emoji'];
//            $use_short_attachment = $config['use_short_attachment'];
//            $include_context_and_extra = $config['include_context_and_extra'];
//
//            $slackHandler = new SlackWebhookHandler($webhook_url, $channel, $username, $use_attachment, $icon_emoji, $use_short_attachment, $include_context_and_extra, $level, $bubble, $exclude_fields);
//            $slackHandler->setFormatter($formatter);
//        } else {
//            $slackHandler = new StreamHandler('php://stdout', $level); // 输出到标准输出
//        }
//
//        $this->logger->pushHandler($slackHandler);
    }

    public function __call($name, $arguments)
    {
        return $this->logger->$name(...$arguments);
    }

}