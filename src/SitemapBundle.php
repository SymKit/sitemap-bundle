<?php

declare(strict_types=1);

namespace Symkit\SitemapBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symkit\SitemapBundle\Cache\SitemapCacheManager;
use Symkit\SitemapBundle\Cache\SitemapCacheWarmer;
use Symkit\SitemapBundle\Command\SitemapDebugCommand;
use Symkit\SitemapBundle\Command\SitemapGenerateCommand;
use Symkit\SitemapBundle\Contract\SitemapCacheInvalidatorInterface;
use Symkit\SitemapBundle\Contract\SitemapCacheManagerInterface;
use Symkit\SitemapBundle\Contract\SitemapGeneratorInterface;
use Symkit\SitemapBundle\Contract\SitemapLoaderInterface;
use Symkit\SitemapBundle\Contract\SitemapNotifierInterface;
use Symkit\SitemapBundle\Contract\SitemapPriorityCalculatorInterface;
use Symkit\SitemapBundle\Contract\SitemapProviderInterface;
use Symkit\SitemapBundle\Contract\SitemapRegistryInterface;
use Symkit\SitemapBundle\Controller\SitemapController;
use Symkit\SitemapBundle\DataCollector\SitemapDataCollector;
use Symkit\SitemapBundle\Event\SitemapInvalidateEvent;
use Symkit\SitemapBundle\Event\SitemapPostGenerateEvent;
use Symkit\SitemapBundle\Event\SitemapPreGenerateEvent;
use Symkit\SitemapBundle\EventListener\RobotsTxtListener;
use Symkit\SitemapBundle\EventListener\SitemapCacheInvalidator;
use Symkit\SitemapBundle\EventListener\SitemapIndexNowListener;
use Symkit\SitemapBundle\Generator\SitemapGenerator;
use Symkit\SitemapBundle\Generator\SitemapIndexGenerator;
use Symkit\SitemapBundle\Generator\SitemapUrlGenerator;
use Symkit\SitemapBundle\MessageHandler\GenerateSitemapHandler;
use Symkit\SitemapBundle\Notifier\IndexNowNotifier;
use Symkit\SitemapBundle\Priority\SitemapPriorityCalculator;
use Symkit\SitemapBundle\Provider\SitemapProvider;
use Symkit\SitemapBundle\Registry\SitemapRegistry;

class SitemapBundle extends AbstractBundle
{
    public function getPath(): string
    {
        return \dirname(parent::getPath());
    }

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
                ->booleanNode('gzip')
                    ->defaultFalse()
                    ->info('Enable gzip compression for sitemap responses.')
                ->end()
                ->arrayNode('robots_txt')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultFalse()
                        ->end()
                        ->scalarNode('content')
                            ->defaultNull()
                            ->info('Full robots.txt content. If set, the bundle serves /robots.txt with this content.')
                        ->end()
                        ->booleanNode('inject_sitemap')
                            ->defaultTrue()
                            ->info('Auto-append Sitemap: directive to robots.txt responses.')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('messenger')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultFalse()
                        ->end()
                        ->scalarNode('transport')
                            ->defaultValue('async')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('index_now')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultFalse()
                        ->end()
                        ->scalarNode('api_key')
                            ->defaultNull()
                        ->end()
                        ->scalarNode('host')
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    /**
     * @param array{
     *     cache: array{enabled: bool, pool: string, tag: string, ttl: int},
     *     items_per_page: int,
     *     gzip: bool,
     *     robots_txt: array{enabled: bool, content: ?string, inject_sitemap: bool},
     *     messenger: array{enabled: bool, transport: string},
     *     index_now: array{enabled: bool, api_key: ?string, host: ?string},
     * } $config
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

        $services->set(SitemapGenerator::class)
            ->tag('monolog.logger', ['channel' => 'sitemap']);
        $services->alias(SitemapGeneratorInterface::class, SitemapGenerator::class);

        $services->set(SitemapCacheManager::class)
            ->arg('$cache', $config['cache']['enabled'] ? service($config['cache']['pool']) : null)
            ->arg('$enabled', $config['cache']['enabled'])
            ->arg('$tag', $config['cache']['tag'])
            ->arg('$ttl', $config['cache']['ttl'])
            ->tag('monolog.logger', ['channel' => 'sitemap']);
        $services->alias(SitemapCacheManagerInterface::class, SitemapCacheManager::class);

        $services->set(SitemapProvider::class);
        $services->alias(SitemapProviderInterface::class, SitemapProvider::class);

        $services->set(SitemapController::class)
            ->arg('$gzip', $config['gzip'])
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

        // Console commands
        $services->set(SitemapGenerateCommand::class)
            ->arg('$itemsPerPage', $itemsPerPage)
            ->tag('console.command');

        $services->set(SitemapDebugCommand::class)
            ->arg('$itemsPerPage', $itemsPerPage)
            ->tag('console.command');

        // Cache warmer (only when cache is enabled)
        if ($config['cache']['enabled']) {
            $services->set(SitemapCacheWarmer::class)
                ->arg('$itemsPerPage', $itemsPerPage)
                ->tag('kernel.cache_warmer');
        }

        // Profiler data collector (only when profiler is available)
        if ($builder->hasDefinition('profiler') || $builder->hasAlias('profiler')) {
            $services->set(SitemapDataCollector::class)
                ->arg('$cacheEnabled', $config['cache']['enabled'])
                ->tag('data_collector', [
                    'template' => '@SymkitSitemap/data_collector/sitemap.html.twig',
                    'id' => 'symkit_sitemap',
                ])
                ->tag('kernel.event_listener', [
                    'event' => SitemapPreGenerateEvent::class,
                    'method' => 'onPreGenerate',
                ])
                ->tag('kernel.event_listener', [
                    'event' => SitemapPostGenerateEvent::class,
                    'method' => 'onPostGenerate',
                ]);
        }

        // robots.txt listener
        if ($config['robots_txt']['enabled']) {
            $services->set(RobotsTxtListener::class)
                ->arg('$content', $config['robots_txt']['content'])
                ->arg('$injectSitemap', $config['robots_txt']['inject_sitemap'])
                ->tag('kernel.event_listener', [
                    'event' => 'kernel.request',
                    'method' => 'onKernelRequest',
                    'priority' => 256,
                ])
                ->tag('kernel.event_listener', [
                    'event' => 'kernel.response',
                    'method' => 'onKernelResponse',
                ]);
        }

        // Messenger async generation
        if ($config['messenger']['enabled'] && $builder->hasDefinition('messenger.default_bus')) {
            $services->set(GenerateSitemapHandler::class)
                ->tag('messenger.message_handler');
        }

        // IndexNow notifier
        if ($config['index_now']['enabled'] && null !== $config['index_now']['api_key']) {
            $services->set(IndexNowNotifier::class)
                ->arg('$apiKey', $config['index_now']['api_key'])
                ->arg('$host', $config['index_now']['host'])
                ->tag('monolog.logger', ['channel' => 'sitemap']);
            $services->alias(SitemapNotifierInterface::class, IndexNowNotifier::class);

            $services->set(SitemapIndexNowListener::class)
                ->tag('kernel.event_listener', [
                    'event' => SitemapInvalidateEvent::class,
                    'method' => 'onInvalidate',
                ]);
        }
    }
}
