<?php

namespace App\Component\Cron;

use App\Component\EntityExtension\EntityNameResolver;

class CronModuleFactory implements CronModuleFactoryInterface
{
    /**
     * @var \App\Component\EntityExtension\EntityNameResolver
     */
    protected $entityNameResolver;

    /**
     * @param \App\Component\EntityExtension\EntityNameResolver $entityNameResolver
     */
    public function __construct(EntityNameResolver $entityNameResolver)
    {
        $this->entityNameResolver = $entityNameResolver;
    }

    /**
     * @param string $serviceId
     * @return \App\Component\Cron\CronModule
     */
    public function create(string $serviceId): CronModule
    {
        $classData = $this->entityNameResolver->resolve(CronModule::class);

        return new $classData($serviceId);
    }
}
