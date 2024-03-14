<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Command\Test;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;

#[Command]
class DownloadReportFile extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('test:download-report-file');
    }

    public function handle(): void
    {
        // 创建一个 MockHandler 实例，并传入模拟的响应
        $mock = new MockHandler([
            //            new Response(200, [], 'Success'),          // 模拟成功的响应
            //            new Response(404, [], 'Not Found'),        // 模拟 404 Not Found
            new Response(500, [], 'Internal Server Error'),  // 模拟 500 Internal Server Error
        ]);

        // 创建一个 HandlerStack，将 MockHandler 加入其中
        $handlerStack = HandlerStack::create($mock);

        // 创建 GuzzleHttp 客户端，并指定 HandlerStack
        $client = new Client(['handler' => $handlerStack]);

        // 文件保存路径
        $filePath = BASE_PATH . '/runtime/fake-report-file.txt';

        try {
            // 发起 GET 请求获取文件内容
            $response = $client->get('https://tortuga-prod-na.s3-external-1.amazonaws.com/21c7448c-b953-47b3-af2e-dfea5cce6ec5.amzn1.tortuga.4.na.T34T1VQDCK38XJ?X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Date=20240314T002105Z&X-Amz-SignedHeaders=host&X-Amz-Expires=300&X-Amz-Credential=AKIA5U6MO6RAN4LCK54B%2F20240314%2Fus-east-1%2Fs3%2Faws4_request&X-Amz-Signature=2519ecfbca82acf0ec8992da1acd7c973fafe3a0eac8b26e30d31e6e2471c31a');

            // 将响应内容写入到文件中
            file_put_contents($filePath, $response->getBody());

            echo 'File downloaded successfully!';
        } catch (GuzzleException $e) {
            var_dump($e->getCode()); // HTTP Code
            // 处理异常
            echo 'Error: ' . $e->getMessage();
        }
    }
}
