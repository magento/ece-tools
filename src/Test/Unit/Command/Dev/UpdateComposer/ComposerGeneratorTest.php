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
            'branch' => '2.0.0',
        ],
        'repo3' => [
            'repo' => 'path_to_repo3',
            'branch' => '3.0.0',
        ],
        'repo4' => [
            'repo' => 'path_to_repo4',
            'branch' => '4.0.0',
        ]
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
    protected function setUp()
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

    public function testGetInstallFromGitScripts()
    {
        $this->assertInstallFromGitScripts($this->composerGenerator->getInstallFromGitScripts($this->repoOptions));
    }

    public function testGenerate(): void
    {
        $composer = $this->composerGenerator->generate($this->repoOptions);

        $expected = include(__DIR__ . '/_files/expected_composer.php');
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $composer);
            $this->assertEquals($value, $composer[$key]);
        }
    }

    /**
     * @param array $actual
     *
     * @return void
     */
    private function assertInstallFromGitScripts(array $actual): void
    {
        $this->assertEquals(
            [
                'php -r"@mkdir(__DIR__ . \'/app/etc\', 0777, true);"',
                'rm -rf repo1 repo2 repo3 repo4',
                'git clone path_to_repo1 "repo1" && git --git-dir="repo1/.git" --work-tree="repo1" checkout 1.0.0',
                'git clone path_to_repo2 "repo2" && git --git-dir="repo2/.git" --work-tree="repo2" checkout 2.0.0',
                'git clone path_to_repo3 "repo3" && git --git-dir="repo3/.git" --work-tree="repo3" checkout 3.0.0',
                'git clone path_to_repo4 "repo4" && git --git-dir="repo4/.git" --work-tree="repo4" checkout 4.0.0',
            ],
            $actual
        );
    }
}
