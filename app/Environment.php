<?php

declare(strict_types=1);

namespace App;

use Composer\IO\IOInterface;
use Composer\Script\Event;
use App\Component\Environment\EnvironmentFileSetting;
use App\Component\Environment\EnvironmentType;

class Environment
{
    /**
     * @var \App\Component\Environment\EnvironmentFileSetting|null
     */
    private static $environmentFileSetting;

    /**
     * @param \Composer\Script\Event $event
     */
    public static function checkEnvironment(Event $event)
    {
        $io = $event->getIO();
        /* @var $io \Composer\IO\IOInterface */
        $environmentFileSetting = self::getEnvironmentFileSetting();
        if (!$environmentFileSetting->isAnyEnvironmentSet()) {
            $environment = $event->isDevMode() ? EnvironmentType::DEVELOPMENT : EnvironmentType::PRODUCTION;
            $environmentFileSetting->createFileForEnvironment($environment);
            $environmentFilePath = $environmentFileSetting->getEnvironmentFilePath($environment);
            $io->write(sprintf('Created a file "%s" to set the application environment!', $environmentFilePath));
        }
        self::printEnvironmentInfo($io);
    }

    /**
     * @param bool $console
     * @return string
     */
    public static function getEnvironment($console)
    {
        return self::getEnvironmentFileSetting()->getEnvironment($console);
    }

    /**
     * @param \Composer\IO\IOInterface $io
     */
    public static function printEnvironmentInfo(IOInterface $io)
    {
        $io->write("\nEnvironment is <info>" . self::getEnvironment(false) . "</info>\n");
    }

    /**
     * @return \App\Component\Environment\EnvironmentFileSetting
     */
    private static function getEnvironmentFileSetting()
    {
        if (self::$environmentFileSetting === null) {
            self::$environmentFileSetting = new EnvironmentFileSetting(__DIR__ . '/..');
        }
        return self::$environmentFileSetting;
    }
}
