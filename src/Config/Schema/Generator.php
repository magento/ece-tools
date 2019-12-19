<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Schema;

use Magento\MagentoCloud\Config\Schema;
use Magento\MagentoCloud\Package\MagentoVersion;

/**
 * Generates schema in generic format prepared for the output
 */
class Generator
{
    /**
     * @var Schema
     */
    private $schema;

    /**
     * @param Schema $schema
     */
    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
    }

    public function generate(): array
    {
        $data = [];

        foreach ($this->schema->getSchema() as $name => $item) {
            if (empty($item[Schema::SCHEMA_STAGES])) {
                continue;
            }

            $description = $item[Schema::SCHEMA_DESCRIPTION] ?? 'Dummy description';

            $data[$name] = [
                'description' => wordwrap($description, 100),
                'stages' => $item[Schema::SCHEMA_STAGES],
                'magento_version' => $item[Schema::SCHEMA_MAGENTO_VERSION] ?? '>=' . MagentoVersion::MIN_VERSION
            ];

            if (!empty($item[Schema::SCHEMA_EXAMPLES])) {
                $data[$name]['examples'] = $item[Schema::SCHEMA_EXAMPLES];
            }
        }

        return $data;
    }
}
