<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Composer\Composer;
use Magento\MagentoCloud\Config\GlobalSection;
use Magento\MagentoCloud\Config\Validator\Deploy\PhpVersion;
use Composer\Package\Version\VersionParser;
use Composer\Semver\Constraint\ConstraintInterface;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Package\MagentoVersion;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\Validator\ResultInterface;
use Composer\Package\Locker;
use Composer\Repository\RepositoryInterface;
use Composer\Package\PackageInterface;
use Composer\Package\Link;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PhpVersionTest extends TestCase
{
    /**
     * @var Validator\ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @var Composer|MockObject
     */
    private $composerMock;

    /**
     * @var VersionParser|MockObject
     */
    private $versionParserMock;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ConstraintInterface|MockObject
     */
    private $composerConstraintMock;

    /**
     * @var ConstraintInterface|MockObject
     */
    private $phpConstraintMock;

    /**
     * @var GlobalSection|MockObject
     */
    private $globalSectionMock;

    /**
     * @var PhpVersion
     */
    private $phpVersion;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->resultFactoryMock = $this->createMock(Validator\ResultFactory::class);
        $this->composerMock = $this->createMock(Composer::class);
        $this->versionParserMock = $this->createMock(VersionParser::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->globalSectionMock = $this->createMock(GlobalSection::class);

        $this->phpVersion = new PhpVersion(
            $this->composerMock,
            $this->resultFactoryMock,
            $this->versionParserMock,
            $this->magentoVersionMock,
            $this->loggerMock,
            $this->globalSectionMock
        );
    }

    /**
     * @param bool $matchesResult
     * @param string $calledMethod
     * @param ResultInterface|MockObject $resultMock
     * @return void
     * @dataProvider validateDataProvider
     */
    public function testValidateSuccess($matchesResult, $calledMethod, $resultMock)
    {
        $this->setUpComposerMocks();
        $this->versionParserMock->expects($this->exactly(2))
            ->method('parseConstraints')
            ->willReturnMap([
                ['~7.2.0', $this->composerConstraintMock],
                [preg_replace('#^([^~+-]+).*$#', '$1', PHP_VERSION), $this->phpConstraintMock]
            ]);
        $this->composerConstraintMock->expects($this->once())
            ->method('matches')
            ->with($this->phpConstraintMock)
            ->willReturn($matchesResult);
        $this->resultFactoryMock->expects($this->once())
            ->method($calledMethod)
            ->willReturn($resultMock);

        $this->assertSame($resultMock, $this->phpVersion->validate());
    }

    /**
     * @return array
     */
    public function validateDataProvider(): array
    {
        return [
            ['matchesResult' => false, 'calledMethod' => 'error', 'resultMock' => $this->createMock(Error::class)],
            ['matchesResult' => true, 'calledMethod' => 'success', 'resultMock' => $this->createMock(Success::class)],
        ];
    }

    /**
     * @return void
     */
    public function testValidateException()
    {
        $this->setUpComposerMocks();
        $resultMock = $this->createMock(Success::class);
        $this->versionParserMock->expects($this->any())
            ->method('parseConstraints')
            ->willThrowException(new \Exception('some error'));
        $this->resultFactoryMock->expects($this->once())
            ->method('success')
            ->willReturn($resultMock);
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with('Can\'t validate version of PHP: some error');

        $this->assertSame($resultMock, $this->phpVersion->validate());
    }

    public function testValidationSuccessInstallFromGit()
    {
        $this->globalSectionMock->expects($this->once())
            ->method('get')
            ->with(GlobalSection::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT)
            ->willReturn('2.2');
        $this->versionParserMock->expects($this->never())
            ->method('parseConstraints');
        $this->composerMock->expects($this->never())
            ->method('getLocker');

        $this->assertInstanceOf(Success::class, $this->phpVersion->validate());
    }

    /**
     * Configure composer mocks
     *
     * @return void
     */
    protected function setUpComposerMocks()
    {
        $constraintMock = $this->getMockForAbstractClass(ConstraintInterface::class);
        $linkMock = $this->createMock(Link::class);
        $packageMock = $this->getMockForAbstractClass(PackageInterface::class);
        $repoMock = $this->getMockForAbstractClass(RepositoryInterface::class);
        $lockerMock = $this->createMock(Locker::class);
        $this->composerConstraintMock = $this->getMockForAbstractClass(ConstraintInterface::class);
        $this->phpConstraintMock = $this->getMockForAbstractClass(ConstraintInterface::class);

        $constraintMock->expects($this->once())
            ->method('getPrettyString')
            ->willReturn('~7.1.13|~7.2.0');
        $linkMock->expects($this->once())
            ->method('getConstraint')
            ->willReturn($constraintMock);
        $packageMock->expects($this->once())
            ->method('getRequires')
            ->willReturn(['php' => $linkMock]);
        $repoMock->expects($this->once())
            ->method('findPackage')
            ->with('magento/magento2-base', '*')
            ->willReturn($packageMock);
        $lockerMock->expects($this->once())
            ->method('getLockedRepository')
            ->willReturn($repoMock);
        $this->composerMock->expects($this->once())
            ->method('getLocker')
            ->willReturn($lockerMock);
    }
}
