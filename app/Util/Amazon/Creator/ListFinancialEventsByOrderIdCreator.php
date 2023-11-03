<?php

namespace App\Util\Amazon\Creator;

class ListFinancialEventsByOrderIdCreator implements CreatorInterface
{
    public string $order_id;

    public int $max_results_per_page = 100;

    /**
     * @return string
     */
    public function getOrderId(): string
    {
        return $this->order_id;
    }

    /**
     * @param string $order_id
     */
    public function setOrderId(string $order_id): void
    {
        $this->order_id = $order_id;
    }

    /**
     * @return int
     */
    public function getMaxResultsPerPage(): int
    {
        return $this->max_results_per_page;
    }

    /**
     * @param int $max_results_per_page
     */
    public function setMaxResultsPerPage(int $max_results_per_page): void
    {
        $this->max_results_per_page = $max_results_per_page;
    }

}