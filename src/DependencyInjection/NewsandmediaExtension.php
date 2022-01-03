<?php

namespace App\DependencyInjection;

use App\Component\Environment\EnvironmentType;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class NewsandmediaExtension extends ConfigurableExtension
{
    /**
     * {@inheritDoc}
     */
    protected function loadInternal(array $config, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        if ($container->getParameter('kernel.environment') === EnvironmentType::TEST) {
            $loader->load('services_test.yml');
        }

        $container->setParameter('newsandmedia.router.locale_router_filepath_mask', $config['router']['locale_router_filepath_mask']);
        $container->setParameter('newsandmedia.router.friendly_url_router_filepath', $config['router']['friendly_url_router_filepath']);
    }
}
