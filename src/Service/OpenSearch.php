<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Service;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Http\ClientFactory;
use Magento\MagentoCloud\Service\Search\AbstractService;
use Magento\MagentoCloud\Package\MagentoVersion;
use Psr\Log\LoggerInterface;

/**
 * Returns OpenSearch service configurations.
 */
class OpenSearch extends AbstractService implements ServiceInterface
{
    protected const RELATIONSHIP_KEY = 'opensearch';
    protected const ENGINE_SHORT_NAME = 'OS';
    public const ENGINE_NAME = 'opensearch';

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @param Environment $environment
     * @param ClientFactory $clientFactory
     * @param LoggerInterface $logger
     * @param MagentoVersion $magentoVersion
     */
    public function __construct(
        Environment $environment,
        ClientFactory $clientFactory,
        LoggerInterface $logger,
        MagentoVersion $magentoVersion
    ) {
        $this->magentoVersion = $magentoVersion;
        parent::__construct($environment, $clientFactory, $logger);
    }

    /**
     * Return full engine name.
     *
     * @return string
     * @throws ServiceException
     */
    public function getFullEngineName(): string
    {
        if ($this->magentoVersion->satisfies('>=2.4.4-p13 <2.4.5 || >=2.4.5-p12')) {
            return static::ENGINE_NAME;
        }

        return 'elasticsearch7';
    }
}
