<?php

namespace App\Component\Router\FriendlyUrl;

use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RouteCollection;

class FriendlyUrlMatcher
{
    /**
     * @var \App\Component\Router\FriendlyUrl\FriendlyUrlRepository
     */
    protected $friendlyUrlRepository;

    /**
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrlRepository $friendlyUrlRepository
     */
    public function __construct(FriendlyUrlRepository $friendlyUrlRepository)
    {
        $this->friendlyUrlRepository = $friendlyUrlRepository;
    }

    /**
     * @param string $pathinfo
     * @param \Symfony\Component\Routing\RouteCollection $routeCollection
     * @return array
     */
    public function match($pathinfo, RouteCollection $routeCollection)
    {
        $pathWithoutSlash = substr($pathinfo, 1);
        $friendlyUrl = $this->friendlyUrlRepository->findBySlug($pathWithoutSlash);

        if ($friendlyUrl === null) {
            throw new ResourceNotFoundException();
        }

        $route = $routeCollection->get($friendlyUrl->getRouteName());
        if ($route === null) {
            throw new ResourceNotFoundException();
        }

        $matchedParameters = $route->getDefaults();
        $matchedParameters['_route'] = $friendlyUrl->getRouteName();
        $matchedParameters['id'] = $friendlyUrl->getEntityId();

        if (!$friendlyUrl->isMain()) {
            $matchedParameters['_controller'] = 'FrameworkBundle:Redirect:redirect';
            $matchedParameters['route'] = $friendlyUrl->getRouteName();
            $matchedParameters['permanent'] = true;
        }

        return $matchedParameters;
    }
}
