<?php

namespace App\Command\Test;

use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;
use function Hyperf\Coroutine\parallel;

#[Command]
class Parallel extends HyperfCommand
{

    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('test:parallel');
    }

    public function handle(): void
    {
        $url_list = [
            'https://leetcode.cn',
//            'https://www.github.com',
            'https://www.baidu.com',
            'https://www.zhihu.com',
            'https://www.bilibili.com',
            'https://www.toutiao.com',
            'https://learnku.com',
            'https://www.ithome.com',
            'https://kimi.moonshot.cn',
            'https://www.speedtest.cn',
        ];

        $parallel = new \Hyperf\Coroutine\Parallel();

        $client = new \GuzzleHttp\Client();
        //并发请求10个url
        foreach ($url_list as $url) {
            $parallel->add(function () use ($url, $client) {
                $start = microtime(true);

                $response = $client->get($url);

                $status_code = $response->getStatusCode();

                $end = microtime(true);
                $cost_time = $end - $start;

                var_dump(sprintf('url:%s status-code:%s 耗时:%s', $url, $status_code, $cost_time));

                return $cost_time;
            });
        }

//        parallel([]);

        try{
            // $results 结果为 [1, 2]
            $results = $parallel->wait();

            var_dump($results);
        } catch(ParallelExecutionException $e){
            // $e->getResults() 获取协程中的返回值。
            // $e->getThrowables() 获取协程中出现的异常。
        }

    }


}