<?php

namespace App\Component\Router\FriendlyUrl;

use App\Component\EntityExtension\EntityNameResolver;
use App\Component\String\TransformString;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FriendlyUrlFactory implements FriendlyUrlFactoryInterface
{
    /**
     * @var \App\Component\EntityExtension\EntityNameResolver
     */
    protected $entityNameResolver;

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @param \App\Component\EntityExtension\EntityNameResolver $entityNameResolver
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(
        EntityNameResolver $entityNameResolver,
        ContainerInterface $container
    ) {
        $this->entityNameResolver = $entityNameResolver;
        $this->container = $container;
    }

    /**
     * @param string $routeName
     * @param int $entityId
     * @param string $slug
     * @return \App\Component\Router\FriendlyUrl\FriendlyUrl
     */
    public function create(
        string $routeName,
        int $entityId,
        string $slug
    ): FriendlyUrl {
        $classData = $this->entityNameResolver->resolve(FriendlyUrl::class);
        return new $classData($routeName, $entityId, $slug);
    }

    /**
     * @param string $routeName
     * @param int $entityId
     * @param string $entityName
     * @param int|null $indexPostfix
     * @return \App\Component\Router\FriendlyUrl\FriendlyUrl|null
     */
    public function createIfValid(
        string $routeName,
        int $entityId,
        string $entityName,
        ?int $indexPostfix = null
    ): ?FriendlyUrl {
        if ($entityName === '') {
            return null;
        }

        $nameForUrl = $entityName . ($indexPostfix === null ? '' : '-' . $indexPostfix);
        $slug = TransformString::stringToFriendlyUrlSlug($nameForUrl) . '/';

        return $this->create($routeName, $entityId, $slug);
    }

    /**
     * @param string $routeName
     * @param int $entityId
     * @param string[] $namesByLocale
     * @return \App\Component\Router\FriendlyUrl\FriendlyUrl[]
     */
    public function createForAllDomains(
        string $routeName,
        int $entityId,
        array $namesByLocale
    ): array {
        $friendlyUrls = [];
        $locale = $this->container->getParameter('locale');

        if (array_key_exists($locale, $namesByLocale)) {
            $friendlyUrl = $this->createIfValid(
                $routeName,
                $entityId,
                (string)$namesByLocale[$locale]
            );

            if ($friendlyUrl !== null) {
                $friendlyUrls[] = $friendlyUrl;
            }
        }

        return $friendlyUrls;
    }
}
