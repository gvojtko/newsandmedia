<?php

namespace App\Component\Router\FriendlyUrl;

use Doctrine\ORM\EntityManagerInterface;
use App\Component\Router\FriendlyUrl\Exception\FriendlyUrlNotFoundException;

class FriendlyUrlRepository
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $em;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function getFriendlyUrlRepository()
    {
        return $this->em->getRepository(FriendlyUrl::class);
    }

    /**
     * @param string $slug
     * @return \App\Component\Router\FriendlyUrl\FriendlyUrl|null
     */
    public function findBySlug($slug)
    {
        return $this->getFriendlyUrlRepository()->findOneBy(
            [
                'slug' => $slug,
            ]
        );
    }

    /**
     * @param string $routeName
     * @param int $entityId
     * @return \App\Component\Router\FriendlyUrl\FriendlyUrl
     */
    public function getMainFriendlyUrl($routeName, $entityId)
    {
        $criteria = [
            'routeName' => $routeName,
            'entityId' => $entityId,
            'main' => true,
        ];
        $friendlyUrl = $this->getFriendlyUrlRepository()->findOneBy($criteria);

        if ($friendlyUrl === null) {
            throw new FriendlyUrlNotFoundException();
        }

        return $friendlyUrl;
    }

    /**
     * @param string $routeName
     * @param int $entityId
     * @return \App\Component\Router\FriendlyUrl\FriendlyUrl|null
     */
    public function findMainFriendlyUrl($routeName, $entityId)
    {
        $criteria = [
            'routeName' => $routeName,
            'entityId' => $entityId,
            'main' => true,
        ];

        return $this->getFriendlyUrlRepository()->findOneBy($criteria);
    }

    /**
     * @param string $routeName
     * @param int $entityId
     * @return \App\Component\Router\FriendlyUrl\FriendlyUrl[]
     */
    public function getAllByRouteNameAndEntityId($routeName, $entityId)
    {
        $criteria = [
            'routeName' => $routeName,
            'entityId' => $entityId,
        ];

        return $this->getFriendlyUrlRepository()->findBy(
            $criteria,
            [
                'slug' => 'ASC',
            ]
        );
    }

    /**
     * @param string $routeName
     * @param int $entityId
     * @return \App\Component\Router\FriendlyUrl\FriendlyUrl[]
     */
    public function getAllByRouteNameAndEntityIdAndDomainId($routeName, $entityId)
    {
        $criteria = [
            'routeName' => $routeName,
            'entityId' => $entityId,
        ];

        return $this->getFriendlyUrlRepository()->findBy($criteria);
    }

    /**
     * @param object[]|int[] $entitiesOrEntityIds
     * @param string $routeName
     * @return \App\Component\Router\FriendlyUrl\FriendlyUrl[]
     */
    public function getMainFriendlyUrlsByEntitiesIndexedByEntityId(array $entitiesOrEntityIds, $routeName)
    {
        $queryBuilder = $this->em->createQueryBuilder()
            ->select('f')
            ->from(FriendlyUrl::class, 'f', 'f.entityId')
            ->andWhere('f.routeName = :routeName')->setParameter('routeName', $routeName)
            ->andWhere('f.entityId IN (:entities)')->setParameter('entities', $entitiesOrEntityIds)
            ->andWhere('f.main = TRUE');

        return $queryBuilder->getQuery()->execute();
    }
}
