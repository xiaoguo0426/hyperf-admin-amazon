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
}
