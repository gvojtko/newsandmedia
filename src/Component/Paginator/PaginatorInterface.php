<?php

namespace App\Component\Paginator;

interface PaginatorInterface
{
    /**
     * @param mixed $page
     * @param mixed $pageSize
     */
    public function getResult($page, $pageSize);

    public function getTotalCount();
}
