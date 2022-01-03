<?php

namespace App\Component\Router\FriendlyUrl\CompilerPass;

class FriendlyUrlDataProviderRegistry
{
    /**
     * @var \App\Component\Router\FriendlyUrl\CompilerPass\FriendlyUrlDataProviderInterface[]
     */
    protected $friendlyUrlDataProviders;

    public function __construct()
    {
        $this->friendlyUrlDataProviders = [];
    }

    /**
     * @param \App\Component\Router\FriendlyUrl\CompilerPass\FriendlyUrlDataProviderInterface $service
     */
    public function registerFriendlyUrlDataProvider(FriendlyUrlDataProviderInterface $service)
    {
        $this->friendlyUrlDataProviders[] = $service;
    }

    /**
     * @param string $routeName
     * @return \App\Component\Router\FriendlyUrl\FriendlyUrlData[]
     */
    public function getFriendlyUrlDataByRouteAndDomain($routeName)
    {
        foreach ($this->friendlyUrlDataProviders as $friendlyUrlDataProvider) {
            if ($friendlyUrlDataProvider->getRouteName() === $routeName) {
                return $friendlyUrlDataProvider->getFriendlyUrlData();
            }
        }

        throw new \App\Component\Router\FriendlyUrl\Exception\FriendlyUrlRouteNotSupportedException($routeName);
    }
}
