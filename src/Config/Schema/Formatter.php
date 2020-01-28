<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Schema;

use Magento\MagentoCloud\Config\Schema;
use Magento\MagentoCloud\Package\MagentoVersion;
use Symfony\Component\Yaml\Dumper;

/**
 * The Markdown syntax formatter
 */
class Formatter implements FormatterInterface
{
    private const EMPTY_LINE = "\n\n";

    /**
     * @var Dumper
     */
    private $dumper;

    /**
     * @param Dumper $dumper
     */
    public function __construct(Dumper $dumper)
    {
        $this->dumper = $dumper;
    }

    /**
     * @inheritDoc
     */
    public function format(array $data): string
    {
        $text = '';

        foreach ($data as $key => $item) {
            if (!empty($item[Schema::SCHEMA_SKIP_DUMP])) {
                continue;
            }

            $description = $item[Schema::SCHEMA_DESCRIPTION] ?? 'N/A';
            $magentoVersion = $item[Schema::SCHEMA_MAGENTO_VERSION] ?? '>=' . MagentoVersion::MIN_VERSION;

            $text .= sprintf(
                '## %s%s%s%s',
                $key,
                self::EMPTY_LINE,
                wordwrap($description, 120),
                self::EMPTY_LINE
            );

            $text .= $this->wrapTableRow('Attribute', 'Values');
            $text .= $this->wrapTableRow('---', '---');
            $text .= $this->wrapTableRow('Type', $item[Schema::SCHEMA_TYPE]);
            $text .= $this->wrapTableRow('Magento version', '\\' . $magentoVersion);

            if (!empty($item[Schema::SCHEMA_STAGES])) {
                $text .= $this->wrapTableRow('Stages', implode(', ', $item[Schema::SCHEMA_STAGES]));
            }

            if (!empty($item[Schema::SCHEMA_ALLOWED_VALUES])) {
                $allowedValues = array_filter($item[Schema::SCHEMA_ALLOWED_VALUES]);

                $text .= $this->wrapTableRow('Allowed values', implode(', ', $allowedValues));
            }

            $text .= "\n";

            if (!empty($item[Schema::SCHEMA_EXAMPLES])) {
                $text .= '### Examples' . self::EMPTY_LINE;

                foreach ($item[Schema::SCHEMA_EXAMPLES] as $example) {
                    if (!empty($example[Schema::SCHEMA_EXAMPLE_COMMENT])) {
                        $text .= $example[Schema::SCHEMA_EXAMPLE_COMMENT] . self::EMPTY_LINE;

                        unset($example[Schema::SCHEMA_EXAMPLE_COMMENT]);
                    }

                    $text .= $this->wrapCode($this->dumper->dump($example, 6, 2));
                }
            }
        }

        return $text;
    }

    /**
     * @param string $code
     * @param string $lang
     * @return string
     */
    private function wrapCode(string $code, string $lang = 'yaml'): string
    {
        return '```' . ($lang ?: '') . "\n" . $code . "\n" . '```' . self::EMPTY_LINE;
    }

    /**
     * @param string $property
     * @param string $values
     * @return string
     */
    private function wrapTableRow(string $property, string $values): string
    {
        return sprintf("|%s|%s|\n", $property, $values);
    }
}
