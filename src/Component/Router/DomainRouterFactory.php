<?php

namespace App\Component\Router;

use BadMethodCallException;
use App\Component\Environment\EnvironmentType;
use App\Component\Router\Exception\RouterNotResolvedException;
use App\Component\Router\FriendlyUrl\FriendlyUrlRouterFactory;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RequestContext;

class DomainRouterFactory
{
    /**
     * @var \App\Component\Router\LocalizedRouterFactory
     */
    protected $localizedRouterFactory;

    /**
     * @var \App\Component\Router\FriendlyUrl\FriendlyUrlRouterFactory
     */
    protected $friendlyUrlRouterFactory;

    /**
     * @var \Symfony\Component\Config\Loader\LoaderInterface
     */
    protected $configLoader;

    /**
     * @var string
     */
    protected $routerConfiguration;

    /**
     * @var \App\Component\Router\DomainRouter
     */
    protected $router = null;

    /**
     * @var \Symfony\Component\HttpFoundation\RequestStack
     */
    protected $requestStack;

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface|null
     */
    protected ?ContainerInterface $container;

    /**
     * @var string|null
     */
    protected ?string $cacheDir;

    /**
     * @param mixed $routerConfiguration
     * @param \Symfony\Component\Config\Loader\LoaderInterface|null $configLoader
     * @param \App\Component\Router\LocalizedRouterFactory $localizedRouterFactory
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrlRouterFactory $friendlyUrlRouterFactory
     * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
     * @param \Symfony\Component\DependencyInjection\ContainerInterface|null $container
     * @param string|null $cacheDir
     */
    public function __construct(
        $routerConfiguration,
        ?LoaderInterface $configLoader,
        LocalizedRouterFactory $localizedRouterFactory,
        FriendlyUrlRouterFactory $friendlyUrlRouterFactory,
        RequestStack $requestStack,
        ?ContainerInterface $container = null,
        ?string $cacheDir = null
    ) {
        $this->routerConfiguration = $routerConfiguration;
        $this->configLoader = $configLoader;
        $this->localizedRouterFactory = $localizedRouterFactory;
        $this->friendlyUrlRouterFactory = $friendlyUrlRouterFactory;
        $this->requestStack = $requestStack;
        $this->container = $container;
        $this->cacheDir = $cacheDir;
    }

    /**
     * @required
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     * @internal This function will be replaced by constructor injection in next major
     */
    public function setContainer(ContainerInterface $container): void
    {
        if ($this->container !== null && $this->container !== $container) {
            throw new BadMethodCallException(
                sprintf('Method "%s" has been already called and cannot be called multiple times.', __METHOD__)
            );
        }
        if ($this->container !== null) {
            return;
        }

        @trigger_error(
            sprintf(
                'The %s() method is deprecated and will be removed in the next major. Use the constructor injection instead.',
                __METHOD__
            ),
            E_USER_DEPRECATED
        );
        $this->container = $container;
    }

    /**
     * @return \App\Component\Router\DomainRouter
     */
    public function getRouter()
    {
        if (!$this->router) {
            $context = $this->getRequestContext();
            $basicRouter = $this->getBasicRouter();
            $localizedRouter = $this->localizedRouterFactory->getRouter($this->container->getParameter('locale'), $context);
            $friendlyUrlRouter = $this->friendlyUrlRouterFactory->createRouter($context);
            $this->router = new DomainRouter(
                $context,
                $basicRouter,
                $localizedRouter,
                $friendlyUrlRouter
            );
        }

        return $this->router;
    }

    /**
     * @return \Symfony\Component\Routing\Router
     */
    protected function getBasicRouter()
    {
        if ($this->cacheDir === null) {
            $deprecationMessage = sprintf(
                'The argument "$cacheDir" is not provided by constructor in "%s". In the next major it will be required.',
                self::class
            );
            @trigger_error($deprecationMessage, E_USER_DEPRECATED);

            $this->cacheDir = $this->container->getParameter('newsandmedia.router.domain.cache_dir');
        }

        return new Router(
            $this->container,
            $this->routerConfiguration,
            $this->getRouterOptions(),
            $this->getRequestContext()
        );
    }

    /**
     * @return \Symfony\Component\Routing\RequestContext
     */
    protected function getRequestContext()
    {
        $urlComponents = parse_url($this->container->getParameter('domain_url'));
        $requestContext = new RequestContext();
        $request = $this->requestStack->getCurrentRequest();
        if ($request !== null) {
            $requestContext->fromRequest($request);
        }

        if (array_key_exists('path', $urlComponents)) {
            $requestContext->setBaseUrl($urlComponents['path']);
        }

        $requestContext->setScheme($urlComponents['scheme']);
        $requestContext->setHost($urlComponents['host']);

        if (array_key_exists('port', $urlComponents)) {
            if ($urlComponents['scheme'] === 'http') {
                $requestContext->setHttpPort($urlComponents['port']);
            } elseif ($urlComponents['scheme'] === 'https') {
                $requestContext->setHttpsPort($urlComponents['port']);
            }
        }

        return $requestContext;
    }

    /**
     * @return \App\Component\Router\FriendlyUrl\FriendlyUrlRouter
     */
    public function getFriendlyUrlRouter()
    {
        $context = $this->getRequestContext();

        return $this->friendlyUrlRouterFactory->createRouter($context);
    }

    /**
     * @return array
     */
    protected function getRouterOptions(): array
    {
        $options = ['resource_type' => 'service'];

        if ($this->container->getParameter('kernel.environment') !== EnvironmentType::DEVELOPMENT) {
            $options['cache_dir'] = $this->cacheDir;
        }

        return $options;
    }
}
