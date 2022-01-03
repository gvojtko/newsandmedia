<?php

namespace App\Component\Cron;

class CronFilter
{
    /**
     * @param \App\Component\Cron\Config\CronModuleConfig[] $cronModuleConfigs
     * @param string[] $scheduledServiceIds
     * @return \App\Component\Cron\Config\CronModuleConfig[]
     */
    public function filterScheduledCronModuleConfigs(array $cronModuleConfigs, array $scheduledServiceIds)
    {
        foreach ($cronModuleConfigs as $key => $cronModuleConfig) {
            if (!in_array($cronModuleConfig->getServiceId(), $scheduledServiceIds, true)) {
                unset($cronModuleConfigs[$key]);
            }
        }

        return $cronModuleConfigs;
    }
}
