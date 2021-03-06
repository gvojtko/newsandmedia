<?php

namespace App\Command;

use App\Component\Redis\RedisVersionsFacade;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RedisCleanCacheOldCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'newsandmedia:redis:clean-cache-old';

    /**
     * @var \App\Component\Redis\RedisVersionsFacade
     */
    private $redisVersionsFacade;

    /**
     * @param \App\Component\Redis\RedisVersionsFacade $redisVersionsFacade
     */
    public function __construct(RedisVersionsFacade $redisVersionsFacade)
    {
        $this->redisVersionsFacade = $redisVersionsFacade;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Cleans up redis cache for previous build versions');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->redisVersionsFacade->cleanOldCache();

        return CommandResultCodes::RESULT_OK;
    }
}
