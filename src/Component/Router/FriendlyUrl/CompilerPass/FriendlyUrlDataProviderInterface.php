<?php

namespace App\Component\Router\FriendlyUrl\CompilerPass;

interface FriendlyUrlDataProviderInterface
{
    /**
     * Returns friendly url data for generating urls
     *
     * @return \App\Component\Router\FriendlyUrl\FriendlyUrlData[]
     */
    public function getFriendlyUrlData(): array;

    /**
     * Returns route name that specifies for which route should be data provider used
     *
     * @return string
     */
    public function getRouteName(): string;
}
