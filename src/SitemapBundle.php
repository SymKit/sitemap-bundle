<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle;

use Symkit\SitemapBundle\Cache\SitemapCacheManager;
use Symkit\SitemapBundle\Contract\SitemapCacheInvalidatorInterface;
use Symkit\SitemapBundle\Contract\SitemapCacheManagerInterface;
use Symkit\SitemapBundle\Contract\SitemapGeneratorInterface;
use Symkit\SitemapBundle\Contract\SitemapLoaderInterface;
use Symkit\SitemapBundle\Contract\SitemapPriorityCalculatorInterface;
use Symkit\SitemapBundle\Contract\SitemapProviderInterface;
use Symkit\SitemapBundle\Contract\SitemapRegistryInterface;
use Symkit\SitemapBundle\Controller\SitemapController;
use Symkit\SitemapBundle\Event\SitemapInvalidateEvent;
use Symkit\SitemapBundle\EventListener\SitemapCacheInvalidator;
use Symkit\SitemapBundle\Generator\SitemapGenerator;
use Symkit\SitemapBundle\Generator\SitemapIndexGenerator;
use Symkit\SitemapBundle\Generator\SitemapUrlGenerator;
use Symkit\SitemapBundle\Priority\SitemapPriorityCalculator;
use Symkit\SitemapBundle\Provider\SitemapProvider;
use Symkit\SitemapBundle\Registry\SitemapRegistry;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

class SitemapBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->arrayNode('cache')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultFalse()
                        ->end()
                        ->scalarNode('pool')
                            ->defaultValue('cache.app.taggable')
                        ->end()
                        ->scalarNode('tag')
                            ->defaultValue('sitemap')
                        ->end()
                        ->integerNode('ttl')
                            ->defaultValue(3600)
                        ->end()
                    ->end()
                ->end()
                ->integerNode('items_per_page')
                    ->defaultValue(25000)
                    ->info('Maximum number of URLs per sitemap chunk.')
                ->end()
            ->end()
        ;
    }

    /**
     * @param array{cache: array{enabled: bool, pool: string, tag: string, ttl: int}, items_per_page: int} $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->registerForAutoconfiguration(SitemapLoaderInterface::class)
            ->addTag('symkit_sitemap.loader');

        $services = $container->services();
        $services->defaults()
            ->autowire()
            ->autoconfigure();

        $itemsPerPage = $config['items_per_page'];

        $services->set(SitemapRegistry::class)
            ->arg('$loaders', tagged_iterator('symkit_sitemap.loader', indexAttribute: 'index'));
        $services->alias(SitemapRegistryInterface::class, SitemapRegistry::class);

        $services->set(SitemapUrlGenerator::class)
            ->arg('$itemsPerPage', $itemsPerPage);

        $services->set(SitemapIndexGenerator::class)
            ->arg('$itemsPerPage', $itemsPerPage);

        $services->set(SitemapGenerator::class);
        $services->alias(SitemapGeneratorInterface::class, SitemapGenerator::class);

        $services->set(SitemapCacheManager::class)
            ->arg('$cache', $config['cache']['enabled'] ? service($config['cache']['pool']) : null)
            ->arg('$enabled', $config['cache']['enabled'])
            ->arg('$tag', $config['cache']['tag'])
            ->arg('$ttl', $config['cache']['ttl']);
        $services->alias(SitemapCacheManagerInterface::class, SitemapCacheManager::class);

        $services->set(SitemapProvider::class);
        $services->alias(SitemapProviderInterface::class, SitemapProvider::class);

        $services->set(SitemapController::class)
            ->public()
            ->tag('controller.service_arguments');

        $services->set(SitemapPriorityCalculator::class);
        $services->alias(SitemapPriorityCalculatorInterface::class, SitemapPriorityCalculator::class);

        $services->set(SitemapCacheInvalidator::class)
            ->tag('kernel.event_listener', [
                'event' => SitemapInvalidateEvent::class,
                'method' => 'onInvalidate',
            ]);
        $services->alias(SitemapCacheInvalidatorInterface::class, SitemapCacheInvalidator::class);
    }
}
