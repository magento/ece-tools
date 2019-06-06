<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Command\Dev\UpdateComposer;

use Magento\MagentoCloud\Command\Dev\UpdateComposer\ComposerGenerator;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Package\MagentoVersion;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

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
            'branch' => '1.0.0',
        ],
        'repo3' => [
            'repo' => 'path_to_repo3',
            'branch' => '3.0.0',
            'type' => 'single-package'
        ],
    ];

    /**
     * @var DirectoryList|Mock
     */
    private $directoryListMock;

    /**
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @var MagentoVersion|Mock
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
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->fileMock = $this->createMock(File::class);

        $this->composerGenerator = new ComposerGenerator(
            $this->directoryListMock,
            $this->magentoVersionMock,
            $this->fileMock
        );
    }

    public function testGetInstallFromGitScripts()
    {
        $this->assertInstallFromGitScripts($this->composerGenerator->getInstallFromGitScripts($this->repoOptions));
    }

    public function testGenerate()
    {
        $rootDir = '/root';
        $this->directoryListMock->expects($this->any())
            ->method('getMagentoRoot')
            ->willReturn($rootDir);
        $this->magentoVersionMock->expects($this->once())
            ->method('getVersion')
            ->willReturn('2.2');
        $this->fileMock->expects($this->exactly(5))
            ->method('isExists')
            ->willReturn(true);
        $this->fileMock->expects($this->exactly(5))
            ->method('fileGetContents')
            ->willReturnOnConsecutiveCalls(
                '{"require": {"package": "*"}, "repositories": {"repo": {"type": "git", "url": "url"}}}',
                '{"name": "repo1", "require": {"package/require1": "*"}, "repositories": ' .
                '{"repo1": {"type": "git", "url": "url"}}}',
                '{"name": "repo2", "require": {"package/require2": "*", "magento/module-test": ">102.0.0"}, ' .
                '"repositories": {"repo2": {"type": "git", "url": "url"}}}',
                '{"name": "repo3", "require": {"package/require3": "*", "magento/framework": ">100.1.1"}, ' .
                '"repositories": {"repo3": {"type": "git", "url": "url"}}}',
                '{"name": "repo3", "require": {"package/require3": "*", "magento/framework": ">100.1.1"}, ' .
                '"repositories": {"repo3": {"type": "git", "url": "url"}}}'
            );

        $composer = $this->composerGenerator->generate($this->repoOptions);

        $this->assertArrayHasKey('version', $composer);
        $this->assertEquals('2.2', $composer['version']);

        $this->assertArrayHasKey('scripts', $composer);
        $this->assertArrayHasKey('install-from-git', $composer['scripts']);
        $this->assertEquals(
            [
                "rsync -azh --stats --exclude='app/code/Magento/' --exclude='app/i18n/' --exclude='app/design/'"
                . " --exclude='dev/tests' --exclude='lib/internal/Magento' --exclude='.git' ./repo1/ ./",
                "rsync -azh --stats --exclude='app/code/Magento/' --exclude='app/i18n/' --exclude='app/design/'"
                . " --exclude='dev/tests' --exclude='lib/internal/Magento' --exclude='.git' ./repo2/ ./",
            ],
            $composer['scripts']['prepare-packages']
        );
        $this->assertInstallFromGitScripts($composer['scripts']['install-from-git']);

        $this->assertArrayHasKey('require', $composer);
        $this->assertArrayHasKey('package/require1', $composer['require']);
        $this->assertArrayHasKey('package/require2', $composer['require']);
        $this->assertArrayHasKey('package/require3', $composer['require']);
        $this->assertArrayHasKey('magento/framework', $composer['require']);
        $this->assertArrayHasKey('magento/module-test', $composer['require']);

        $this->assertEquals('*', $composer['require']['magento/framework']);
        $this->assertEquals('*', $composer['require']['magento/module-test']);

        $this->assertArrayHasKey('repositories', $composer);
        $this->assertArrayHasKey('repo3', $composer['repositories']);
    }

    /**
     * @param array $actual
     *
     * @return void
     */
    private function assertInstallFromGitScripts(array $actual)
    {
        $this->assertEquals(
            [
                'php -r"@mkdir(__DIR__ . \'/app/etc\', 0777, true);"',
                'rm -rf repo1 repo2 repo3',
                'git clone -b 1.0.0 --single-branch --depth 1 path_to_repo1 repo1',
                'git clone -b 1.0.0 --single-branch --depth 1 path_to_repo2 repo2',
                'git clone -b 3.0.0 --single-branch --depth 1 path_to_repo3 repo3',
            ],
            $actual
        );
    }
}
