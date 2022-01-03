<?php

namespace App\Component\Cron;

interface CronModuleFactoryInterface
{
    /**
     * @param string $serviceId
     * @return \App\Component\Cron\CronModule
     */
    public function create(string $serviceId): CronModule;
}
