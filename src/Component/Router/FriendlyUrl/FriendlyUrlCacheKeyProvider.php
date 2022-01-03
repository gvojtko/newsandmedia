<?php

declare(strict_types=1);

namespace App\Component\Router\FriendlyUrl;

class FriendlyUrlCacheKeyProvider
{
    /**
     * @param string $routeName
     * @param int $entityId
     * @return string
     */
    public function getMainFriendlyUrlSlugCacheKey(string $routeName, int $entityId): string
    {
        return sprintf(
            '%s_%s',
            $routeName,
            $entityId
        );
    }
}
