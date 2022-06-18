<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Command\Dev\UpdateComposer;

use Magento\MagentoCloud\Command\Dev\UpdateComposer\ComposerGenerator;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Package\MagentoVersion;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ComposerGeneratorTest extends TestCase
{
    /**
     * @var array
     */
    private $repoOptions = [
        'repo1' => [
            'repo' => 'path_to_repo1',
            'branch' => '1.0.0',
        ],
        'repo2' => [
            'repo' => 'path_to_repo2',
            'ref' => '',
            'branch' => '2.0.0',
        ],
        'repo3' => [
            'repo' => 'path_to_repo3',
            'ref' => 'ref3',
            'branch' => '3.0.0',
        ],
        'repo4' => [
            'repo' => 'path_to_repo4',
            'ref' => 'ref4',
            'branch' => '4.0.0',
        ],
        'repo5' => [
            'repo' => 'path_to_repo5',
            'ref' => 'ref5',
            'branch' => '5.0.0',
        ],
    ];

    /**
     * @var array
     */
    private $installFromGitScript = [
        'php -r"@mkdir(__DIR__ . \'/app/etc\', 0777, true);"',
        'rm -rf repo1 repo2 repo3 repo4 repo5',
        'git clone -b 1.0.0 --single-branch --depth 1 path_to_repo1 repo1',
        'git clone -b 2.0.0 --single-branch --depth 1 path_to_repo2 repo2',
        'git clone path_to_repo3 "repo3" && git --git-dir="repo3/.git" --work-tree="repo3" checkout ref3',
        'git clone path_to_repo4 "repo4" && git --git-dir="repo4/.git" --work-tree="repo4" checkout ref4',
        'git clone path_to_repo5 "repo5" && git --git-dir="repo5/.git" --work-tree="repo5" checkout ref5',
        'mv repo1/lib/internal/Magento/Framework/Lib1 repo1/lib/internal/Magento/Framework-Lib1',
    ];

    /**
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @var ComposerGenerator
     */
    private $composerGenerator;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->directoryListMock->expects($this->any())
            ->method('getMagentoRoot')
            ->willReturn(__DIR__ . '/_files/app');
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->magentoVersionMock->expects($this->any())
            ->method('getVersion')
            ->willReturn('2.2');

        $this->composerGenerator = new ComposerGenerator(
            $this->directoryListMock,
            $this->magentoVersionMock,
            new File(),
            '/^((?!exclude).)*$/'
        );
    }

    public function testScripts(): void
    {
        $expected = include(__DIR__ . '/_files/expected_composer.php');
        $installFromGitScripts = $this->composerGenerator->getInstallFromGitScripts($this->repoOptions);
        $frameworkPreparationScript = $this->composerGenerator->getFrameworkPreparationScript(
            array_keys($this->repoOptions)
        );

        $this->assertEquals(
            $expected['scripts']['install-from-git'],
            array_merge($installFromGitScripts, $frameworkPreparationScript)
        );
    }

    public function testGenerate(): void
    {
        $composer = $this->composerGenerator->generate($this->repoOptions, $this->installFromGitScript);

        $expected = include(__DIR__ . '/_files/expected_composer.php');
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $composer);
            $this->assertEquals($value, $composer[$key]);
        }
    }
}
