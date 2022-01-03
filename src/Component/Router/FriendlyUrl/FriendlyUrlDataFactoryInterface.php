<?php

namespace App\Component\Router\FriendlyUrl;

interface FriendlyUrlDataFactoryInterface
{
    /**
     * @return \App\Component\Router\FriendlyUrl\FriendlyUrlData
     */
    public function create(): FriendlyUrlData;

    /**
     * @param int $id
     * @param string $name
     * @return \App\Component\Router\FriendlyUrl\FriendlyUrlData
     */
    public function createFromIdAndName(int $id, string $name): FriendlyUrlData;
}
