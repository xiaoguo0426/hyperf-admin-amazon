<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Util\Amazon\Creator;

class ListFinancialEventsByGroupIdCreator implements CreatorInterface
{
    public string $group_id;

    public int $max_results_per_page = 100;

    public ?\DateTimeInterface $posted_after = null;

    public ?\DateTimeInterface $posted_before = null;

    public function getGroupId(): string
    {
        return $this->group_id;
    }

    public function setGroupId(string $group_id): void
    {
        $this->group_id = $group_id;
    }

    public function getMaxResultsPerPage(): int
    {
        return $this->max_results_per_page;
    }

    public function setMaxResultsPerPage(int $max_results_per_page): void
    {
        $this->max_results_per_page = $max_results_per_page;
    }

    public function getPostedAfter(): ?\DateTimeInterface
    {
        return $this->posted_after;
    }

    public function setPostedAfter(?\DateTimeInterface $posted_after): void
    {
        $this->posted_after = $posted_after;
    }

    public function getPostedBefore(): ?\DateTimeInterface
    {
        return $this->posted_before;
    }

    public function setPostedBefore(?\DateTimeInterface $posted_before): void
    {
        $this->posted_before = $posted_before;
    }
}
