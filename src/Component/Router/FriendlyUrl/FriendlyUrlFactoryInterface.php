<?php

namespace App\Component\Router\FriendlyUrl;

interface FriendlyUrlFactoryInterface
{
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
    ): FriendlyUrl;

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
    ): ?FriendlyUrl;

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
    ): array;
}
