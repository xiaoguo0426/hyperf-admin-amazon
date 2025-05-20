<?php

namespace App\Util;

class ConsistentHashing
{
    private array $hashRing = [];

    /**
     * 添加节点到哈希环
     *
     * @param string $nodeName 节点名称
     * @param int $virtualNodes 虚拟节点数量（默认为 3）
     */
    public function addNode(string $nodeName, int $virtualNodes = 3): void
    {
        for ($i = 0; $i < $virtualNodes; $i++) {
            // 生成虚拟节点的哈希值
            $virtualNodeName = "{$nodeName}_{$i}";
            $hashValue = $this->hash($virtualNodeName);
            $this->hashRing[$hashValue] = $nodeName;
        }
        ksort($this->hashRing); // 按哈希值排序
    }

    /**
     * 从哈希环中删除节点
     *
     * @param string $nodeName 节点名称
     */
    public function removeNode(string $nodeName): void
    {
        foreach ($this->hashRing as $hashValue => $node) {
            if (str_starts_with($node, $nodeName)) {
                unset($this->hashRing[$hashValue]);
            }
        }
        ksort($this->hashRing); // 重新排序
    }

    /**
     * 获取数据对应的节点
     *
     * @param string $key 数据的键
     * @return string|null 返回对应的节点名称，如果没有节点则返回 null
     */
    public function getNode(string $key): ?string
    {
        if (empty($this->hashRing)) {
            return null;
        }

        $hashValue = $this->hash($key);
        $keys = array_keys($this->hashRing);

        // 查找顺时针方向最近的节点
        foreach ($keys as $keyValue) {
            if ($keyValue >= $hashValue) {
                return $this->hashRing[$keyValue];
            }
        }

        // 如果没有找到，返回第一个节点
        reset($this->hashRing);
        return current($this->hashRing);
    }

    /**
     * 计算哈希值
     *
     * @param string $key 需要计算哈希的键
     * @return int 哈希值
     */
    private function hash(string $key): int
    {
        return crc32($key);
    }
}