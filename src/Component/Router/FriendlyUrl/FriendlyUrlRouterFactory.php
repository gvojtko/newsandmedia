<?php

namespace App\Component\Router\FriendlyUrl;

use BadMethodCallException;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Contracts\Cache\CacheInterface;

class FriendlyUrlRouterFactory
{
    /**
     * @var \Symfony\Component\Config\Loader\LoaderInterface
     */
    protected $configLoader;

    /**
     * @var \App\Component\Router\FriendlyUrl\FriendlyUrlRepository
     */
    protected $friendlyUrlRepository;

    /**
     * @var string
     */
    protected $friendlyUrlRouterResourceFilepath;

    /**
     * @var \App\Component\Router\FriendlyUrl\FriendlyUrlCacheKeyProvider|null
     */
    protected ?FriendlyUrlCacheKeyProvider $friendlyUrlCacheKeyProvider;

    /**
     * @var \Symfony\Contracts\Cache\CacheInterface|null
     */
    protected ?CacheInterface $mainFriendlyUrlSlugCache;

    /**
     * @param mixed $friendlyUrlRouterResourceFilepath
     * @param \Symfony\Component\Config\Loader\LoaderInterface $configLoader
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrlRepository $friendlyUrlRepository
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrlCacheKeyProvider|null $friendlyUrlCacheKeyProvider
     * @param \Symfony\Contracts\Cache\CacheInterface|null $mainFriendlyUrlSlugCache
     */
    public function __construct(
        $friendlyUrlRouterResourceFilepath,
        LoaderInterface $configLoader,
        FriendlyUrlRepository $friendlyUrlRepository,
        ?FriendlyUrlCacheKeyProvider $friendlyUrlCacheKeyProvider = null,
        ?CacheInterface $mainFriendlyUrlSlugCache = null
    ) {
        $this->friendlyUrlRouterResourceFilepath = $friendlyUrlRouterResourceFilepath;
        $this->configLoader = $configLoader;
        $this->friendlyUrlRepository = $friendlyUrlRepository;
        $this->friendlyUrlCacheKeyProvider = $friendlyUrlCacheKeyProvider;
        $this->mainFriendlyUrlSlugCache = $mainFriendlyUrlSlugCache;
    }

    /**
     * @param \Symfony\Component\Routing\RequestContext $context
     * @return \App\Component\Router\FriendlyUrl\FriendlyUrlRouter
     */
    public function createRouter(RequestContext $context)
    {
        return new FriendlyUrlRouter(
            $context,
            $this->configLoader,
            new FriendlyUrlGenerator(
                $context,
                $this->friendlyUrlRepository,
                $this->friendlyUrlCacheKeyProvider,
                $this->mainFriendlyUrlSlugCache
            ),
            new FriendlyUrlMatcher($this->friendlyUrlRepository),
            $this->friendlyUrlRouterResourceFilepath
        );
    }

    /**
     * @required
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrlCacheKeyProvider $friendlyUrlCacheKeyProvider
     * @internal This function will be replaced by constructor injection in next major
     */
    public function setFriendlyUrlCacheKeyProvider(FriendlyUrlCacheKeyProvider $friendlyUrlCacheKeyProvider): void
    {
        if (
            $this->friendlyUrlCacheKeyProvider !== null
            && $this->friendlyUrlCacheKeyProvider !== $friendlyUrlCacheKeyProvider
        ) {
            throw new BadMethodCallException(
                sprintf('Method "%s" has been already called and cannot be called multiple times.', __METHOD__)
            );
        }
        if ($this->friendlyUrlCacheKeyProvider !== null) {
            return;
        }

        @trigger_error(
            sprintf(
                'The %s() method is deprecated and will be removed in the next major. Use the constructor injection instead.',
                __METHOD__
            ),
            E_USER_DEPRECATED
        );
        $this->friendlyUrlCacheKeyProvider = $friendlyUrlCacheKeyProvider;
    }
}
