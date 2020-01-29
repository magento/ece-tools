<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\App;

use Composer\Composer;
use Composer\Factory;
use Composer\IO\BufferIO;
use Magento\MagentoCloud\ExtensionRegistrar;
use Magento\MagentoCloud\Filesystem\SystemList;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Exception;

/**
 * @inheritdoc
 * @codeCoverageIgnore
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Container implements ContainerInterface
{
    /**
     * @var \Symfony\Component\DependencyInjection\Container
     */
    private $container;

    /**
     * @param string $toolsBasePath
     * @param string $magentoBasePath
     * @throws ContainerException
     */
    public function __construct(string $toolsBasePath, string $magentoBasePath)
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->set('container', $this);
        $containerBuilder->setDefinition('container', new Definition(__CLASS__))
            ->setArguments([$toolsBasePath, $magentoBasePath]);

        $systemList = new SystemList($toolsBasePath, $magentoBasePath);

        $containerBuilder->set(SystemList::class, $systemList);
        $containerBuilder->setDefinition(SystemList::class, new Definition(SystemList::class));

        $containerBuilder->set(Composer::class, $this->createComposerInstance($systemList));
        $containerBuilder->setDefinition(Composer::class, new Definition(Composer::class));

        try {
            $loader = new XmlFileLoader($containerBuilder, new FileLocator($toolsBasePath . '/config/'));
            $loader->load('services.xml');

            foreach (ExtensionRegistrar::getPaths() as $extensionPath) {
                if (file_exists($extensionPath . '/config/services.xml')) {
                    $loader = new XmlFileLoader($containerBuilder, new FileLocator($extensionPath . '/config/'));
                    $loader->load('services.xml');
                }
            }
        } catch (Exception $exception) {
            throw new ContainerException($exception->getMessage(), $exception->getCode(), $exception);
        }

        $containerBuilder->compile();

        $this->container = $containerBuilder;
    }

    /**
     * @param SystemList $systemList
     * @return Composer
     */
    private function createComposerInstance(SystemList $systemList): Composer
    {
        $composerFactory = new Factory();
        $composerFile = file_exists($systemList->getMagentoRoot() . '/composer.json')
            ? $systemList->getMagentoRoot() . '/composer.json'
            : $systemList->getRoot() . '/composer.json';

        return $composerFactory->createComposer(
            new BufferIO(),
            $composerFile,
            false,
            $systemList->getMagentoRoot()
        );
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
    public function set(string $id, $service): void
    {
        $this->container->set($id, $service);
    }

    /**
     * @inheritdoc
     */
    public function create(string $abstract, array $params = [])
    {
        if (empty($params) && $this->has($abstract)) {
            return $this->get($abstract);
        }

        return new $abstract(...array_values($params));
    }
}
