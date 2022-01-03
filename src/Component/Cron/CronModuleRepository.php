<?php

namespace App\Component\Cron;

use Doctrine\ORM\EntityManagerInterface;

class CronModuleRepository
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $em;

    /**
     * @var \App\Component\Cron\CronModuleFactoryInterface
     */
    protected $cronModuleFactory;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Component\Cron\CronModuleFactoryInterface $cronModuleFactory
     */
    public function __construct(EntityManagerInterface $em, CronModuleFactoryInterface $cronModuleFactory)
    {
        $this->em = $em;
        $this->cronModuleFactory = $cronModuleFactory;
    }

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function getCronModuleRepository()
    {
        return $this->em->getRepository(CronModule::class);
    }

    /**
     * @param string $serviceId
     * @return \App\Component\Cron\CronModule
     */
    public function getCronModuleByServiceId($serviceId)
    {
        $cronModule = $this->getCronModuleRepository()->find($serviceId);
        if ($cronModule === null) {
            $cronModule = $this->cronModuleFactory->create($serviceId);
            $this->em->persist($cronModule);
            $this->em->flush();
        }

        return $cronModule;
    }

    /**
     * @return string[]
     */
    public function getAllScheduledCronModuleServiceIds()
    {
        $query = $this->em->createQuery(
            'SELECT cm.serviceId FROM ' . CronModule::class . ' cm WHERE cm.scheduled = TRUE'
        );

        return array_map('array_pop', $query->getScalarResult());
    }

    /**
     * @return \App\Component\Cron\CronModule[]
     */
    public function findAllIndexedByServiceId(): array
    {
        return $this->getCronModuleRepository()->createQueryBuilder('cm')
            ->indexBy('cm', 'cm.serviceId')
            ->getQuery()->getResult();
    }
}
