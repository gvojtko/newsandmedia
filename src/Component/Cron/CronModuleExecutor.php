<?php

namespace App\Component\Cron;

use DateTimeImmutable;
use App\Component\Cron\IteratedCronModuleInterface;
use App\Component\Cron\SimpleCronModuleInterface;

class CronModuleExecutor
{
    public const RUN_STATUS_OK = 'ok';
    public const RUN_STATUS_TIMEOUT = 'timeout';
    public const RUN_STATUS_SUSPENDED = 'suspended';

    /**
     * @var \DateTimeImmutable|null
     */
    protected $canRunTo;

    /**
     * @param int $secondsTimeout
     */
    public function __construct($secondsTimeout)
    {
        $this->canRunTo = new DateTimeImmutable('+' . $secondsTimeout . ' sec');
    }

    /**
     * @param \App\Component\Cron\SimpleCronModuleInterface|\App\Component\Cron\IteratedCronModuleInterface $cronModuleService
     * @param bool $suspended
     * @return string
     */
    public function runModule($cronModuleService, $suspended)
    {
        if (!$this->canRun()) {
            return self::RUN_STATUS_TIMEOUT;
        }

        if ($cronModuleService instanceof SimpleCronModuleInterface) {
            $cronModuleService->run();

            return self::RUN_STATUS_OK;
        }

        if ($cronModuleService instanceof IteratedCronModuleInterface) {
            if ($suspended) {
                $cronModuleService->wakeUp();
            }
            $inProgress = true;
            while ($this->canRun() && $inProgress === true) {
                $inProgress = $cronModuleService->iterate();
            }
            if ($inProgress === true) {
                $cronModuleService->sleep();
                return self::RUN_STATUS_SUSPENDED;
            }
            return self::RUN_STATUS_OK;
        }

        return self::RUN_STATUS_OK;
    }

    /**
     * @return bool
     */
    public function canRun()
    {
        return $this->canRunTo > new DateTimeImmutable();
    }
}
