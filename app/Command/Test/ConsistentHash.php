<?php

namespace App\Command\Test;

use App\Util\ConsistentHashing;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;

#[Command]
class ConsistentHash extends HyperfCommand
{

    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('test:consistent-hash');
    }

    public function handle(): void
    {
        //一致性哈希算法
        // 示例用法
        $hash = new ConsistentHashing();

// 添加节点
        $hash->addNode("Node A");
        $hash->addNode("Node B");
        $hash->addNode("Node C");

// 分配数据到节点
        echo "Key1 -> " . $hash->getNode("Key1") . PHP_EOL; // 输出 Node B
        echo "Key2 -> " . $hash->getNode("Key2") . PHP_EOL; // 输出 Node A
        echo "Key3 -> " . $hash->getNode("Key3") . PHP_EOL; // 输出 Node C

// 删除一个节点
        $hash->removeNode("Node B");

// 重新分配数据
        echo "After removing Node B:" . PHP_EOL;
        echo "Key1 -> " . $hash->getNode("Key1") . PHP_EOL; // 输出 Node C
        echo "Key2 -> " . $hash->getNode("Key2") . PHP_EOL; // 输出 Node A
        echo "Key3 -> " . $hash->getNode("Key3") . PHP_EOL; // 输出 Node C

    }


}