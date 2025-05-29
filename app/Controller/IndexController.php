<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Controller;

use App\Kernel\Log\Log;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;

#[Controller]
class IndexController extends AbstractController
{
    public function index()
    {
        $user = $this->request->input('user', 'Hyperf');
        $method = $this->request->getMethod();

        Log::get('test','default')->debug("User {$user} -> {$method} {$method}");
        Log::get('test','default')->debug("User {$user} -> {$method} {$method}");

        return [
            'method' => $method,
            'message' => "Hello {$user}.",
        ];
    }
    #[RequestMapping(path: "test2",methods: "post")]
    public function test2()
    {
        return [
            'aa'=>11
        ];
    }

    protected function test()
    {
        echo 1111;
    }

    private function foo()
    {
        echo 2222;
    }
}
