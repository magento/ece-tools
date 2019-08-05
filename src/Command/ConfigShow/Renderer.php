<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command\ConfigShow;

use Magento\MagentoCloud\Config\Environment;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Output encoded cloud configuration environment variables
 */
class Renderer
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @param LoggerInterface $logger
     * @param Environment $environment
     */
    public function __construct(
        LoggerInterface $logger,
        Environment $environment
    ) {
        $this->logger = $logger;
        $this->environment = $environment;
    }

    /**
     * @param OutputInterface $output
     */
    public function printRelationships(OutputInterface $output)
    {
        $rows = [];
        foreach ($this->environment->getRelationships() as $service => $serviceConfig) {
            if (!empty($rows)) {
                $rows[] = new TableSeparator();
            }
            $rows[] = [new TableCell("<comment>{$service}:</comment>", ['colspan' => 2])];
            $rows[] = new TableSeparator();
            foreach ($serviceConfig as $config) {
                $rows = array_merge($rows, $this->buildArray($config));
            }
        }
        $this->renderTable(
            $output,
            'Magento Cloud Services',
            ['Service configuration', 'Value'],
            $rows
        );
    }

    /**
     * @param OutputInterface $output
     */
    public function printRoutes(OutputInterface $output)
    {
        $rows = [];
        foreach ($this->environment->getRoutes() as $route => $config) {
            if (!empty($rows)) {
                $rows[] = new TableSeparator();
            }
            $rows[] = [new TableCell("<comment>{$route}:</comment>", ['colspan' => 2])];
            $rows[] = new TableSeparator();
            $rows = array_merge($rows, $this->buildArray($config));
        }
        $this->renderTable(
            $output,
            'Magento Cloud Routes',
            ['Route configuration', 'Value'],
            $rows
        );
    }

    /**
     * @param OutputInterface $output
     */
    public function printVariables(OutputInterface $output)
    {
        $rows = $this->buildArray($this->environment->getVariables());

        $this->renderTable(
            $output,
            'Magento Cloud Environment Variables',
            ['Variable name', 'Value'],
            $rows
        );
    }

    /**
     * @param OutputInterface $output
     * @param string $title
     * @param array $header
     * @param array $rows
     */
    protected function renderTable(OutputInterface $output, string $title, array $header, array $rows)
    {
        $output->writeln(PHP_EOL . "<info>{$title}:</info>");
        $table = new Table($output);
        $table->setHeaders($header)
            ->setRows($rows);
        if (method_exists($table, 'setColumnWidth')) {
            $table->setColumnWidth(0, 40);
            $table->setColumnWidth(1, 60);
        }
        $table->render();
    }

    /**
     * @param array $data
     * @param int $depth
     * @return array
     */
    protected function buildArray(array $data, $depth = 0)
    {
        $rows = [];
        foreach ($data as $name => $value) {
            if (is_array($value)) {
                $rows[] = [$this->indentValue($name, $depth)];
                $rows = array_merge($rows, $this->buildArray($value, $depth + 1));
            } else {
                if (is_null($value)) {
                    $value = 'null';
                } elseif (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                }
                $rows[] = [$this->indentValue($name, $depth), $value];
            }
        }
        return $rows;
    }

    /**
     * @param mixed $name
     * @param int $depth
     * @return string
     */
    protected function indentValue($name, $depth)
    {
        if (!$depth) {
            return $name;
        }
        return str_repeat(" ", $depth) . '-' . $name;
    }
}
