<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\App;

use Composer\Composer;
use Magento\MagentoCloud\Filesystem\SystemList;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * @inheritdoc
 * @codeCoverageIgnore
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SymfonyContainer implements ContainerInterface
{
    /**
     * @var \Symfony\Component\DependencyInjection\Container
     */
    private $container;

    /**
     * @param string $toolsBasePath
     * @param string $magentoBasePath
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @throws \Exception
     */
    public function __construct(string $toolsBasePath, string $magentoBasePath)
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->set('container', $containerBuilder);
        $containerBuilder->setDefinition('container', new Definition(SymfonyContainer::class))
            ->setArguments([$toolsBasePath, $magentoBasePath]);

        $systemList = new SystemList($toolsBasePath, $magentoBasePath);

        $containerBuilder->set(SystemList::class, $systemList);
        $containerBuilder->setDefinition(SystemList::class, new Definition(SystemList::class));

        $containerBuilder->set(Composer::class, $this->createComposerInstance($systemList));
        $containerBuilder->setDefinition(Composer::class, new Definition(Composer::class));

        $definition = new Definition();
        $definition->setPublic(true);
        $definition->setAutowired(true);
        $definition->setAutoconfigured(true);

        $loader = new XmlFileLoader($containerBuilder, new FileLocator($toolsBasePath . '/src/etc/'));
        $loader->load('services.xml');
        $loader->load('services_build.xml');
        $containerBuilder->compile();

        $this->container = $containerBuilder;
    }

    /**
     * @param SystemList $systemList
     * @return Composer
     */
    private function createComposerInstance(SystemList $systemList): Composer
    {
        $composerFactory = new \Composer\Factory();
        $composerFile = file_exists($systemList->getMagentoRoot() . '/composer.json')
            ? $systemList->getMagentoRoot() . '/composer.json'
            : $systemList->getRoot() . '/composer.json';

        $composer = $composerFactory->createComposer(
            new \Composer\IO\BufferIO(),
            $composerFile,
            false,
            $systemList->getMagentoRoot()
        );

        return $composer;
    }

    /**
     * {@inheritdoc}
     *
     * @see create() For factory-like usage
     */
    public function get($id)
    {
        return $this->container->get($id);
    }

    /**
     * @inheritdoc
     */
    public function has($id): bool
    {
        return $this->container->has($id);
    }

    /**
     * @inheritdoc
     */
    public function set(string $abstract, $concrete, bool $shared = true)
    {
        $this->container->set($abstract, $concrete, $shared);
    }

    /**
     * @inheritdoc
     */
    public function create(string $abstract, array $params = [])
    {
        return $this->container->get($abstract, $params);
    }
}
