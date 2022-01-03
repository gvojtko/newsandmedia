<?php

namespace App\Component\Router\FriendlyUrl;

use App\Component\Router\DomainRouterFactory;
use App\Component\Router\FriendlyUrl\CompilerPass\FriendlyUrlDataProviderRegistry;
use Symfony\Component\Console\Output\OutputInterface;

class FriendlyUrlGeneratorFacade
{
    /**
     * @var \App\Component\Router\DomainRouterFactory
     */
    protected $domainRouterFactory;

    /**
     * @var \App\Component\Router\FriendlyUrl\FriendlyUrlFacade
     */
    protected $friendlyUrlFacade;

    /**
     * @var \App\Component\Router\FriendlyUrl\CompilerPass\FriendlyUrlDataProviderRegistry
     */
    protected $friendlyUrlDataProviderConfig;

    /**
     * @param \App\Component\Router\DomainRouterFactory $domainRouterFactory
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade
     * @param \App\Component\Router\FriendlyUrl\CompilerPass\FriendlyUrlDataProviderRegistry $friendlyUrlDataProviderConfig
     */
    public function __construct(
        DomainRouterFactory $domainRouterFactory,
        FriendlyUrlFacade $friendlyUrlFacade,
        FriendlyUrlDataProviderRegistry $friendlyUrlDataProviderConfig
    ) {
        $this->domainRouterFactory = $domainRouterFactory;
        $this->friendlyUrlFacade = $friendlyUrlFacade;
        $this->friendlyUrlDataProviderConfig = $friendlyUrlDataProviderConfig;
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function generateUrlsForSupportedEntities(OutputInterface $output)
    {
        $output->writeln(' Start of generating friendly urls');

        $countOfCreatedUrls = $this->generateUrls($output);

        $output->writeln(sprintf(
            ' End of generating friendly urls (%d).',
            $countOfCreatedUrls
        ));
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int
     */
    protected function generateUrls(OutputInterface $output)
    {
        $totalCountOfCreatedUrls = 0;
        $friendlyUrlRouter = $this->domainRouterFactory->getFriendlyUrlRouter();

        foreach ($friendlyUrlRouter->getRouteCollection() as $routeName => $route) {
            $countOfCreatedUrls = $this->generateUrlsByRoute($routeName);
            $totalCountOfCreatedUrls += $countOfCreatedUrls;

            $output->writeln(sprintf(
                '   -> route %s in %s (%d)',
                $routeName,
                $route->getDefault('_controller'),
                $countOfCreatedUrls
            ));
        }

        return $totalCountOfCreatedUrls;
    }

    /**
     * @param string $routeName
     * @return int
     */
    protected function generateUrlsByRoute($routeName)
    {
        $countOfCreatedUrls = 0;

        $friendlyUrlsData = $this->friendlyUrlDataProviderConfig->getFriendlyUrlDataByRouteAndDomain(
            $routeName,
        );

        foreach ($friendlyUrlsData as $friendlyUrlData) {
            $this->friendlyUrlFacade->createFriendlyUrlForDomain(
                $routeName,
                $friendlyUrlData->id,
                $friendlyUrlData->name,
            );
            $countOfCreatedUrls++;
        }

        return $countOfCreatedUrls;
    }
}
