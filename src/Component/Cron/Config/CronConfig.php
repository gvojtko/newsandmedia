<?php

namespace App\Component\Cron\Config;

use DateTimeInterface;
use App\Component\Cron\Config\Exception\CronModuleConfigNotFoundException;
use App\Component\Cron\CronTimeResolver;
use App\Component\Cron\Exception\InvalidCronModuleException;
use App\Component\Cron\IteratedCronModuleInterface;
use App\Component\Cron\SimpleCronModuleInterface;

class CronConfig
{
    /**
     * @var \App\Component\Cron\CronTimeResolver
     */
    protected $cronTimeResolver;

    /**
     * @var \App\Component\Cron\Config\CronModuleConfig[]
     */
    protected $cronModuleConfigs;

    /**
     * @param \App\Component\Cron\CronTimeResolver $cronTimeResolver
     */
    public function __construct(CronTimeResolver $cronTimeResolver)
    {
        $this->cronTimeResolver = $cronTimeResolver;
        $this->cronModuleConfigs = [];
    }

    /**
     * @param \App\Component\Cron\SimpleCronModuleInterface|\App\Component\Cron\IteratedCronModuleInterface|mixed $service
     * @param string $serviceId
     * @param string $timeHours
     * @param string $timeMinutes
     * @param string $instanceName
     * @param string|null $readableName
     */
    public function registerCronModuleInstance($service, string $serviceId, string $timeHours, string $timeMinutes, string $instanceName, ?string $readableName = null): void
    {
        if (!$service instanceof SimpleCronModuleInterface && !$service instanceof IteratedCronModuleInterface) {
            throw new InvalidCronModuleException($serviceId);
        }
        $this->cronTimeResolver->validateTimeString($timeHours, 23, 1);
        $this->cronTimeResolver->validateTimeString($timeMinutes, 55, 5);

        $cronModuleConfig = new CronModuleConfig($service, $serviceId, $timeHours, $timeMinutes, $readableName);
        $cronModuleConfig->assignToInstance($instanceName);

        $this->cronModuleConfigs[] = $cronModuleConfig;
    }

    /**
     * @return \App\Component\Cron\Config\CronModuleConfig[]
     */
    public function getAllCronModuleConfigs()
    {
        return $this->cronModuleConfigs;
    }

    /**
     * @param \DateTimeInterface $roundedTime
     * @return \App\Component\Cron\Config\CronModuleConfig[]
     */
    public function getCronModuleConfigsByTime(DateTimeInterface $roundedTime)
    {
        $matchedCronConfigs = [];

        foreach ($this->cronModuleConfigs as $cronConfig) {
            if ($this->cronTimeResolver->isValidAtTime($cronConfig, $roundedTime)) {
                $matchedCronConfigs[] = $cronConfig;
            }
        }

        return $matchedCronConfigs;
    }

    /**
     * @param string $serviceId
     * @return \App\Component\Cron\Config\CronModuleConfig
     */
    public function getCronModuleConfigByServiceId($serviceId)
    {
        foreach ($this->cronModuleConfigs as $cronConfig) {
            if ($cronConfig->getServiceId() === $serviceId) {
                return $cronConfig;
            }
        }

        throw new CronModuleConfigNotFoundException($serviceId);
    }

    /**
     * @param string $instanceName
     * @return array
     */
    public function getCronModuleConfigsForInstance(string $instanceName): array
    {
        $matchedCronConfigs = [];

        foreach ($this->cronModuleConfigs as $cronModuleConfig) {
            if ($cronModuleConfig->getInstanceName() === $instanceName) {
                $matchedCronConfigs[] = $cronModuleConfig;
            }
        }

        return $matchedCronConfigs;
    }
}
