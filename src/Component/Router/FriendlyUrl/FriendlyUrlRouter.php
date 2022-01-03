<?php

namespace App\Component\Router\FriendlyUrl;

use App\Component\Router\FriendlyUrl\Exception\FriendlyUrlRouteNotFoundException;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

class FriendlyUrlRouter implements RouterInterface
{
    /**
     * @var \Symfony\Component\Routing\RequestContext
     */
    protected $context;

    /**
     * @var \Symfony\Component\Config\Loader\LoaderInterface
     */
    protected $configLoader;

    /**
     * @var \App\Component\Router\FriendlyUrl\FriendlyUrlGenerator
     */
    protected $friendlyUrlGenerator;

    /**
     * @var \App\Component\Router\FriendlyUrl\FriendlyUrlMatcher
     */
    protected $friendlyUrlMatcher;

    /**
     * @var string
     */
    protected $friendlyUrlRouterResourceFilepath;

    /**
     * @var \Symfony\Component\Routing\RouteCollection
     */
    protected $collection;

    /**
     * @param \Symfony\Component\Routing\RequestContext $context
     * @param \Symfony\Component\Config\Loader\LoaderInterface $configLoader
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrlGenerator $friendlyUrlGenerator
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrlMatcher $friendlyUrlMatcher
     * @param string $friendlyUrlRouterResourceFilepath
     */
    public function __construct(
        RequestContext $context,
        LoaderInterface $configLoader,
        FriendlyUrlGenerator $friendlyUrlGenerator,
        FriendlyUrlMatcher $friendlyUrlMatcher,
        $friendlyUrlRouterResourceFilepath
    ) {
        $this->context = $context;
        $this->configLoader = $configLoader;
        $this->friendlyUrlGenerator = $friendlyUrlGenerator;
        $this->friendlyUrlMatcher = $friendlyUrlMatcher;
        $this->friendlyUrlRouterResourceFilepath = $friendlyUrlRouterResourceFilepath;
    }

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(RequestContext $context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollection()
    {
        if ($this->collection === null) {
            $this->collection = $this->configLoader->load($this->friendlyUrlRouterResourceFilepath);
        }

        return $this->collection;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($routeName, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        return $this->friendlyUrlGenerator->generateFromRouteCollection(
            $this->getRouteCollection(),
            $routeName,
            $parameters,
            $referenceType
        );
    }

    /**
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrl $friendlyUrl
     * @param array $parameters
     * @param int $referenceType
     * @return string
     */
    public function generateByFriendlyUrl(FriendlyUrl $friendlyUrl, array $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        $routeName = $friendlyUrl->getRouteName();
        $route = $this->getRouteCollection()->get($routeName);

        if ($route === null) {
            throw new FriendlyUrlRouteNotFoundException(
                $routeName,
                $this->friendlyUrlRouterResourceFilepath
            );
        }

        return $this->friendlyUrlGenerator->getGeneratedUrl(
            $routeName,
            $route,
            $friendlyUrl,
            $parameters,
            $referenceType
        );
    }

    /**
     * {@inheritdoc}
     */
    public function match($pathinfo)
    {
        return $this->friendlyUrlMatcher->match($pathinfo, $this->getRouteCollection());
    }
}
