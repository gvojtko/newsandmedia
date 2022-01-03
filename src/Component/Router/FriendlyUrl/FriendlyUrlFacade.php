<?php

declare(strict_types=1);

namespace App\Component\Router\FriendlyUrl;

use BadMethodCallException;
use Doctrine\ORM\EntityManagerInterface;
use App\Component\Router\DomainRouterFactory;
use App\Component\Router\FriendlyUrl\Exception\FriendlyUrlNotFoundException;
use App\Component\Router\FriendlyUrl\Exception\ReachMaxUrlUniqueResolveAttemptException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Contracts\Cache\CacheInterface;

class FriendlyUrlFacade
{
    protected const MAX_URL_UNIQUE_RESOLVE_ATTEMPT = 100;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $em;

    /**
     * @var \App\Component\Router\DomainRouterFactory
     */
    protected $domainRouterFactory;

    /**
     * @var \App\Component\Router\FriendlyUrl\FriendlyUrlUniqueResultFactory
     */
    protected $friendlyUrlUniqueResultFactory;

    /**
     * @var \App\Component\Router\FriendlyUrl\FriendlyUrlRepository
     */
    protected $friendlyUrlRepository;

    /**
     * @var \App\Component\Router\FriendlyUrl\FriendlyUrlFactoryInterface
     */
    protected $friendlyUrlFactory;

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @var \App\Component\Router\FriendlyUrl\FriendlyUrlCacheKeyProvider|null
     */
    protected ?FriendlyUrlCacheKeyProvider $friendlyUrlCacheKeyProvider;

    /**
     * @var \Symfony\Contracts\Cache\CacheInterface|null
     */
    protected ?CacheInterface $mainFriendlyUrlSlugCache;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Component\Router\DomainRouterFactory $domainRouterFactory
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrlUniqueResultFactory $friendlyUrlUniqueResultFactory
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrlRepository $friendlyUrlRepository
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrlFactoryInterface $friendlyUrlFactory
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrlCacheKeyProvider|null $friendlyUrlCacheKeyProvider
     * @param \Symfony\Contracts\Cache\CacheInterface|null $mainFriendlyUrlSlugCache
     */
    public function __construct(
        EntityManagerInterface $em,
        DomainRouterFactory $domainRouterFactory,
        FriendlyUrlUniqueResultFactory $friendlyUrlUniqueResultFactory,
        FriendlyUrlRepository $friendlyUrlRepository,
        FriendlyUrlFactoryInterface $friendlyUrlFactory,
        ContainerInterface $container,
        ?FriendlyUrlCacheKeyProvider $friendlyUrlCacheKeyProvider = null,
        ?CacheInterface $mainFriendlyUrlSlugCache = null
    ) {
        if ($mainFriendlyUrlSlugCache === null) {
            $deprecationMessage = sprintf(
                'The argument "$mainFriendlyUrlSlugCache" is not provided by constructor in "%s". In the next major it will be required.',
                self::class
            );
            @trigger_error($deprecationMessage, E_USER_DEPRECATED);
        }

        $this->em = $em;
        $this->domainRouterFactory = $domainRouterFactory;
        $this->friendlyUrlUniqueResultFactory = $friendlyUrlUniqueResultFactory;
        $this->friendlyUrlRepository = $friendlyUrlRepository;
        $this->friendlyUrlFactory = $friendlyUrlFactory;
        $this->container = $container;
        $this->mainFriendlyUrlSlugCache = $mainFriendlyUrlSlugCache;
        $this->friendlyUrlCacheKeyProvider = $friendlyUrlCacheKeyProvider;
    }

    /**
     * @param string $routeName
     * @param int $entityId
     * @param string[] $namesByLocale
     */
    public function createFriendlyUrls($routeName, $entityId, array $namesByLocale)
    {
        $friendlyUrls = $this->friendlyUrlFactory->createForAllDomains($routeName, $entityId, $namesByLocale);
        foreach ($friendlyUrls as $friendlyUrl) {
            $locale = $this->container->getParameter('locale');
            $this->resolveUniquenessOfFriendlyUrlAndFlush($friendlyUrl, $namesByLocale[$locale]);
        }
    }

    /**
     * @param string $routeName
     * @param int $entityId
     * @param string $entityName
     */
    public function createFriendlyUrlForDomain($routeName, $entityId, $entityName)
    {
        $friendlyUrl = $this->friendlyUrlFactory->createIfValid($routeName, $entityId, (string)$entityName);
        if ($friendlyUrl !== null) {
            $this->resolveUniquenessOfFriendlyUrlAndFlush($friendlyUrl, $entityName);
        }
    }

    /**
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrl $friendlyUrl
     * @param string $entityName
     */
    protected function resolveUniquenessOfFriendlyUrlAndFlush(FriendlyUrl $friendlyUrl, $entityName)
    {
        $attempt = 0;
        do {
            $attempt++;
            if ($attempt > static::MAX_URL_UNIQUE_RESOLVE_ATTEMPT) {
                throw new ReachMaxUrlUniqueResolveAttemptException(
                    $friendlyUrl,
                    $attempt
                );
            }

            $domainRouter = $this->domainRouterFactory->getRouter();
            try {
                $matchedRouteData = $domainRouter->match('/' . $friendlyUrl->getSlug());
            } catch (ResourceNotFoundException $e) {
                $matchedRouteData = null;
            }

            $friendlyUrlUniqueResult = $this->friendlyUrlUniqueResultFactory->create(
                $attempt,
                $friendlyUrl,
                (string)$entityName,
                $matchedRouteData
            );
            $friendlyUrl = $friendlyUrlUniqueResult->getFriendlyUrlForPersist();
        } while (!$friendlyUrlUniqueResult->isUnique());

        if ($friendlyUrl === null) {
            return;
        }

        $this->em->persist($friendlyUrl);
        $this->em->flush();
        $this->setFriendlyUrlAsMain($friendlyUrl);
    }

    /**
     * @param string $routeName
     * @param int $entityId
     * @return \App\Component\Router\FriendlyUrl\FriendlyUrl[]
     */
    public function getAllByRouteNameAndEntityId($routeName, $entityId)
    {
        return $this->friendlyUrlRepository->getAllByRouteNameAndEntityId($routeName, $entityId);
    }

    /**
     * @param string $routeName
     * @param int $entityId
     * @return \App\Component\Router\FriendlyUrl\FriendlyUrl|null
     */
    public function findMainFriendlyUrl($routeName, $entityId)
    {
        return $this->friendlyUrlRepository->findMainFriendlyUrl($routeName, $entityId);
    }

    /**
     * @param string $routeName
     * @param int $entityId
     * @return string
     */
    public function getAbsoluteUrlByRouteNameAndEntityId(string $routeName, int $entityId): string
    {
        $mainFriendlyUrl = $this->findMainFriendlyUrl($routeName, $entityId);

        if ($mainFriendlyUrl === null) {
            throw new FriendlyUrlNotFoundException();
        }

        return $this->getAbsoluteUrlByFriendlyUrl($mainFriendlyUrl);
    }

    /**
     * @param string $routeName
     * @param int $entityId
     * @return string
     */
    public function getAbsoluteUrlByRouteNameAndEntityIdOnCurrentDomain(string $routeName, int $entityId): string
    {
        return $this->getAbsoluteUrlByRouteNameAndEntityId($routeName, $entityId);
    }

    /**
     * @param string $routeName
     * @param int $entityId
     * @param \App\Component\Router\FriendlyUrl\UrlListData $urlListData
     */
    public function saveUrlListFormData($routeName, $entityId, UrlListData $urlListData)
    {
        $toFlush = [];

        foreach ($urlListData->mainFriendlyUrls as $friendlyUrl) {
            if ($friendlyUrl !== null) {
                $this->setFriendlyUrlAsMain($friendlyUrl);
                $toFlush[] = $friendlyUrl;
            }
        }

        foreach ($urlListData->toDelete as $friendlyUrls) {
            foreach ($friendlyUrls as $friendlyUrl) {
                if (!$friendlyUrl->isMain()) {
                    $this->em->remove($friendlyUrl);
                    $toFlush[] = $friendlyUrl;
                }
            }
        }

        foreach ($urlListData->newUrls as $urlData) {
            $domainId = $urlData[UrlListData::FIELD_DOMAIN];
            $newSlug = $urlData[UrlListData::FIELD_SLUG];
            $newFriendlyUrl = $this->friendlyUrlFactory->create($routeName, $entityId, $domainId, $newSlug);
            $this->em->persist($newFriendlyUrl);
            $toFlush[] = $newFriendlyUrl;
        }

        if (count($toFlush) > 0) {
            $this->em->flush();
        }
    }

    /**
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrl $mainFriendlyUrl
     */
    protected function setFriendlyUrlAsMain(FriendlyUrl $mainFriendlyUrl)
    {
        $friendlyUrls = $this->friendlyUrlRepository->getAllByRouteNameAndEntityIdAndDomainId(
            $mainFriendlyUrl->getRouteName(),
            $mainFriendlyUrl->getEntityId(),
            $mainFriendlyUrl->getDomainId()
        );
        foreach ($friendlyUrls as $friendlyUrl) {
            $friendlyUrl->setMain(false);
        }
        $mainFriendlyUrl->setMain(true);
        $this->renewMainFriendlyUrlSlugCache($mainFriendlyUrl);

        $this->em->flush();
    }

    /**
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrl $friendlyUrl
     * @return string
     */
    public function getAbsoluteUrlByFriendlyUrl(FriendlyUrl $friendlyUrl): string
    {
        return $this->container->getParameter('domain_url') . '/' . $friendlyUrl->getSlug();
    }

    /**
     * @param string $routeName
     * @param int $entityId
     */
    public function removeFriendlyUrlsForAllDomains(string $routeName, int $entityId): void
    {
        foreach ($this->getAllByRouteNameAndEntityId($routeName, $entityId) as $friendlyUrl) {
            $this->em->remove($friendlyUrl);
        }

        $this->em->flush();
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

    /**
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrl $mainFriendlyUrl
     */
    protected function renewMainFriendlyUrlSlugCache(FriendlyUrl $mainFriendlyUrl): void
    {
        if ($this->mainFriendlyUrlSlugCache === null) {
            return;
        }

        $cacheKey = $this->friendlyUrlCacheKeyProvider->getMainFriendlyUrlSlugCacheKey(
            $mainFriendlyUrl->getRouteName(),
            $mainFriendlyUrl->getDomainId(),
            $mainFriendlyUrl->getEntityId()
        );
        $this->mainFriendlyUrlSlugCache->delete($cacheKey);
        $this->mainFriendlyUrlSlugCache->get($cacheKey, function () use ($mainFriendlyUrl) {
            return $mainFriendlyUrl->getSlug();
        });
    }
}
