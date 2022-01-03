<?php

declare(strict_types=1);

namespace App\Component\Cron;

use Doctrine\ORM\EntityManagerInterface;
use App\Component\Cron\Config\CronModuleConfig;

class CronModuleFacade
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $em;

    /**
     * @var \App\Component\Cron\CronModuleRepository
     */
    protected $cronModuleRepository;

    /**
     * @var \App\Component\Cron\CronFilter
     */
    protected $cronFilter;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Component\Cron\CronModuleRepository $cronModuleRepository
     * @param \App\Component\Cron\CronFilter $cronFilter
     */
    public function __construct(
        EntityManagerInterface $em,
        CronModuleRepository $cronModuleRepository,
        CronFilter $cronFilter
    ) {
        $this->em = $em;
        $this->cronModuleRepository = $cronModuleRepository;
        $this->cronFilter = $cronFilter;
    }

    /**
     * @param \App\Component\Cron\Config\CronModuleConfig[] $cronModuleConfigs
     */
    public function scheduleModules(array $cronModuleConfigs)
    {
        foreach ($cronModuleConfigs as $cronModuleConfig) {
            $cronModule = $this->cronModuleRepository->getCronModuleByServiceId($cronModuleConfig->getServiceId());
            $cronModule->schedule();
            $this->em->flush();
        }
    }

    /**
     * @param \App\Component\Cron\Config\CronModuleConfig[] $cronModuleConfigs
     * @return \App\Component\Cron\Config\CronModuleConfig[]
     */
    public function getOnlyScheduledCronModuleConfigs(array $cronModuleConfigs)
    {
        $scheduledServiceIds = $this->cronModuleRepository->getAllScheduledCronModuleServiceIds();

        return $this->cronFilter->filterScheduledCronModuleConfigs($cronModuleConfigs, $scheduledServiceIds);
    }

    /**
     * @param \App\Component\Cron\Config\CronModuleConfig $cronModuleConfig
     */
    public function unscheduleModule(CronModuleConfig $cronModuleConfig)
    {
        $cronModule = $this->cronModuleRepository->getCronModuleByServiceId($cronModuleConfig->getServiceId());
        $cronModule->unschedule();
        $this->em->flush();
    }

    /**
     * @param \App\Component\Cron\Config\CronModuleConfig $cronModuleConfig
     */
    public function suspendModule(CronModuleConfig $cronModuleConfig)
    {
        $cronModule = $this->cronModuleRepository->getCronModuleByServiceId($cronModuleConfig->getServiceId());
        $cronModule->suspend();
        $this->em->flush();
    }

    /**
     * @param \App\Component\Cron\Config\CronModuleConfig $cronModuleConfig
     * @return bool
     */
    public function isModuleDisabled(CronModuleConfig $cronModuleConfig): bool
    {
        $cronModule = $this->cronModuleRepository->getCronModuleByServiceId($cronModuleConfig->getServiceId());

        return $cronModule->isEnabled() === false;
    }

    /**
     * @param \App\Component\Cron\Config\CronModuleConfig $cronModuleConfig
     * @return bool
     */
    public function isModuleSuspended(CronModuleConfig $cronModuleConfig)
    {
        $cronModule = $this->cronModuleRepository->getCronModuleByServiceId($cronModuleConfig->getServiceId());

        return $cronModule->isSuspended();
    }

    /**
     * @param \App\Component\Cron\Config\CronModuleConfig $cronModuleConfig
     */
    public function markCronAsStarted(CronModuleConfig $cronModuleConfig): void
    {
        $cronModule = $this->cronModuleRepository->getCronModuleByServiceId($cronModuleConfig->getServiceId());
        $cronModule->setStatusRunning();
        $cronModule->updateLastStartedAt();

        $this->em->flush();
    }

    /**
     * @param \App\Component\Cron\Config\CronModuleConfig $cronModuleConfig
     */
    public function markCronAsEnded(CronModuleConfig $cronModuleConfig): void
    {
        $cronModule = $this->cronModuleRepository->getCronModuleByServiceId($cronModuleConfig->getServiceId());
        $cronModule->setStatusOk();
        $cronModule->updateLastFinishedAt();

        if ($cronModule->getLastStartedAt() !== null) {
            $lastCronDuration = $cronModule->getLastFinishedAt()->getTimestamp() - $cronModule->getLastStartedAt()->getTimestamp();
            $cronModule->setLastDuration($lastCronDuration);
        }

        $this->em->flush();
    }

    /**
     * @param \App\Component\Cron\Config\CronModuleConfig $cronModuleConfig
     */
    public function markCronAsFailed(CronModuleConfig $cronModuleConfig): void
    {
        $cronModule = $this->cronModuleRepository->getCronModuleByServiceId($cronModuleConfig->getServiceId());
        $cronModule->setStatusFailed();

        $this->em->flush();
    }

    /**
     * @param string $serviceId
     */
    public function disableCronModuleByServiceId(string $serviceId): void
    {
        $cronModule = $this->getCronModuleByServiceId($serviceId);
        $cronModule->disable();

        $this->em->flush();
    }

    /**
     * @param string $serviceId
     */
    public function enableCronModuleByServiceId(string $serviceId): void
    {
        $cronModule = $this->getCronModuleByServiceId($serviceId);
        $cronModule->enable();

        $this->em->flush();
    }

    /**
     * @param string $serviceId
     * @return \App\Component\Cron\CronModule
     */
    public function getCronModuleByServiceId(string $serviceId): CronModule
    {
        return $this->cronModuleRepository->getCronModuleByServiceId($serviceId);
    }

    /**
     * @return \App\Component\Cron\CronModule[]
     */
    public function findAllIndexedByServiceId(): array
    {
        return $this->cronModuleRepository->findAllIndexedByServiceId();
    }

    /**
     * @param string $serviceId
     */
    public function schedule(string $serviceId): void
    {
        $cronModule = $this->getCronModuleByServiceId($serviceId);
        $cronModule->schedule();

        $this->em->flush();
    }
}
