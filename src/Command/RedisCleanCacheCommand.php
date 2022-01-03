<?php

namespace App\Command;

use App\Component\Redis\RedisFacade;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RedisCleanCacheCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'newsandmedia:redis:clean-cache';

    /**
     * @var \App\Component\Redis\RedisFacade
     */
    private $redisFacade;

    /**
     * RedisCleanCacheCommand constructor.
     *
     * @param \App\Component\Redis\RedisFacade $redisFacade
     */
    public function __construct(RedisFacade $redisFacade)
    {
        $this->redisFacade = $redisFacade;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Cleans up redis cache');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->redisFacade->cleanCache();

        return CommandResultCodes::RESULT_OK;
    }
}
