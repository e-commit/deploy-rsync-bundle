<?php

declare(strict_types=1);

/*
 * This file is part of the EcommitDeployRsyncBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\DeployRsyncBundle\Tests\Command;

use Ecommit\DeployRsyncBundle\Command\DeployRsyncCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Process\Process;

class DeployRsyncCommandTest extends TestCase
{
    /**
     * @dataProvider getTestSuccessProvider
     */
    public function testSuccess(string $environment, array $expectedCommand): void
    {
        $commandTester = $this->createCommandTester($this->getDefaultConfig(), $expectedCommand, true);
        $exitCode = $commandTester->execute([
            'environment' => $environment,
        ]);

        $this->assertSame("line1\nline2\n", $commandTester->getDisplay(true));
        $this->assertSame(0, $exitCode);
    }

    public function getTestSuccessProvider(): \Generator
    {
        yield ['env1', [
            'rsync',
            '--dry-run',
            '-azC',
            '--force',
            '--delete',
            '--progress',
            '--exclude-from='.realpath(__DIR__.'/../ignore_file.txt'),
            '-e',
            'ssh -p22',
            '/local/path/',
            'username1@host1:/path1/',
        ]];
        yield ['env2', [
            'rsync',
            '--dry-run',
            '-azC',
            '--force',
            '--delete',
            '--progress',
            '--exclude-from='.realpath(__DIR__.'/../ignore_file.txt'),
            '-e',
            'ssh -p23',
            '/local/path/',
            'username2@host2:/path2/',
        ]];
        yield ['env3', [
            'rsync',
            '--dry-run',
            '-azC',
            '--force',
            '--delete',
            '--progress',
            '--exclude-from='.realpath(__DIR__.'/../ignore_file.txt'),
            '/local/path/',
            '/path3/subdir3/',
        ]];
    }

    /**
     * @dataProvider getTestSuccessGoProvider
     */
    public function testSuccessGo(string $environment, array $expectedCommand): void
    {
        $commandTester = $this->createCommandTester($this->getDefaultConfig(), $expectedCommand, true);
        $exitCode = $commandTester->execute([
            'environment' => $environment,
            '--go' => true,
        ]);

        $this->assertSame("line1\nline2\n", $commandTester->getDisplay(true));
        $this->assertSame(0, $exitCode);
    }

    public function getTestSuccessGoProvider(): \Generator
    {
        yield ['env1', [
            'rsync',
            '-azC',
            '--force',
            '--delete',
            '--progress',
            '--exclude-from='.realpath(__DIR__.'/../ignore_file.txt'),
            '-e',
            'ssh -p22',
            '/local/path/',
            'username1@host1:/path1/',
        ]];
        yield ['env2', [
            'rsync',
            '-azC',
            '--force',
            '--delete',
            '--progress',
            '--exclude-from='.realpath(__DIR__.'/../ignore_file.txt'),
            '-e',
            'ssh -p23',
            '/local/path/',
            'username2@host2:/path2/',
        ]];
        yield ['env3', [
            'rsync',
            '-azC',
            '--force',
            '--delete',
            '--progress',
            '--exclude-from='.realpath(__DIR__.'/../ignore_file.txt'),
            '/local/path/',
            '/path3/subdir3/',
        ]];
    }

    public function testWithBadEnv(): void
    {
        $commandTester = $this->createCommandTester($this->getDefaultConfig(), [], false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Environment not found: bad');

        $commandTester->execute([
            'environment' => 'bad',
        ]);
    }

    /**
     * @dataProvider getTestSuccessWithoutIgnoreFileProvider
     */
    public function testSuccessWithoutIgnoreFile(string $environment, array $expectedCommand): void
    {
        $config = $this->getDefaultConfig();
        $config['rsync']['ignore_file'] = null;
        $commandTester = $this->createCommandTester($config, $expectedCommand, true);
        $commandTester->setInputs(['y']);
        $exitCode = $commandTester->execute([
            'environment' => $environment,
        ]);

        $this->assertSame("Continue without ignore file? [y/N]line1\nline2\n", $commandTester->getDisplay(true));
        $this->assertSame(0, $exitCode);
    }

    public function getTestSuccessWithoutIgnoreFileProvider(): \Generator
    {
        yield ['env1', [
            'rsync',
            '--dry-run',
            '-azC',
            '--force',
            '--delete',
            '--progress',
            '-e',
            'ssh -p22',
            '/local/path/',
            'username1@host1:/path1/',
        ]];
        yield ['env2', [
            'rsync',
            '--dry-run',
            '-azC',
            '--force',
            '--delete',
            '--progress',
            '-e',
            'ssh -p23',
            '/local/path/',
            'username2@host2:/path2/',
        ]];
        yield ['env3', [
            'rsync',
            '--dry-run',
            '-azC',
            '--force',
            '--delete',
            '--progress',
            '/local/path/',
            '/path3/subdir3/',
        ]];
    }

    public function testCancelWithoutIgnoreFile(): void
    {
        $config = $this->getDefaultConfig();
        $config['rsync']['ignore_file'] = null;
        $commandTester = $this->createCommandTester($config, [], false);
        $commandTester->setInputs(['n']);
        $exitCode = $commandTester->execute([
            'environment' => 'env1',
        ]);

        $this->assertSame('Continue without ignore file? [y/N]', $commandTester->getDisplay(true));
        $this->assertSame(0, $exitCode);
    }

    /**
     * @dataProvider getTestWithGlobalIgnoreFileAndWithEnvIgnoreFileProvider
     */
    public function testWithGlobalIgnoreFileAndWithEnvIgnoreFile(string $environment, array $expectedCommand): void
    {
        $config = $this->getDefaultConfig();
        $config['environments'][$environment]['ignore_file'] = realpath(__DIR__.'/../ignore_local_file.txt');
        $commandTester = $this->createCommandTester($config, $expectedCommand, true);
        $exitCode = $commandTester->execute([
            'environment' => $environment,
        ]);

        $this->assertSame("line1\nline2\n", $commandTester->getDisplay(true));
        $this->assertSame(0, $exitCode);
    }

    public function getTestWithGlobalIgnoreFileAndWithEnvIgnoreFileProvider(): \Generator
    {
        yield ['env1', [
            'rsync',
            '--dry-run',
            '-azC',
            '--force',
            '--delete',
            '--progress',
            '--exclude-from='.realpath(__DIR__.'/../ignore_local_file.txt'),
            '-e',
            'ssh -p22',
            '/local/path/',
            'username1@host1:/path1/',
        ]];
        yield ['env2', [
            'rsync',
            '--dry-run',
            '-azC',
            '--force',
            '--delete',
            '--progress',
            '--exclude-from='.realpath(__DIR__.'/../ignore_local_file.txt'),
            '-e',
            'ssh -p23',
            '/local/path/',
            'username2@host2:/path2/',
        ]];
        yield ['env3', [
            'rsync',
            '--dry-run',
            '-azC',
            '--force',
            '--delete',
            '--progress',
            '--exclude-from='.realpath(__DIR__.'/../ignore_local_file.txt'),
            '/local/path/',
            '/path3/subdir3/',
        ]];
    }

    /**
     * @dataProvider getTestWithoutGlobalIgnoreFileAndWithEnvIgnoreFileProvider
     */
    public function testWithoutGlobalIgnoreFileAndWithEnvIgnoreFile(string $environment, array $expectedCommand): void
    {
        $config = $this->getDefaultConfig();
        $config['rsync']['ignore_file'] = null;
        $config['environments'][$environment]['ignore_file'] = realpath(__DIR__.'/../ignore_local_file.txt');
        $commandTester = $this->createCommandTester($config, $expectedCommand, true);
        $exitCode = $commandTester->execute([
            'environment' => $environment,
        ]);

        $this->assertSame("line1\nline2\n", $commandTester->getDisplay(true));
        $this->assertSame(0, $exitCode);
    }

    public function getTestWithoutGlobalIgnoreFileAndWithEnvIgnoreFileProvider(): \Generator
    {
        yield ['env1', [
            'rsync',
            '--dry-run',
            '-azC',
            '--force',
            '--delete',
            '--progress',
            '--exclude-from='.realpath(__DIR__.'/../ignore_local_file.txt'),
            '-e',
            'ssh -p22',
            '/local/path/',
            'username1@host1:/path1/',
        ]];
        yield ['env2', [
            'rsync',
            '--dry-run',
            '-azC',
            '--force',
            '--delete',
            '--progress',
            '--exclude-from='.realpath(__DIR__.'/../ignore_local_file.txt'),
            '-e',
            'ssh -p23',
            '/local/path/',
            'username2@host2:/path2/',
        ]];
        yield ['env3', [
            'rsync',
            '--dry-run',
            '-azC',
            '--force',
            '--delete',
            '--progress',
            '--exclude-from='.realpath(__DIR__.'/../ignore_local_file.txt'),
            '/local/path/',
            '/path3/subdir3/',
        ]];
    }

    /**
     * @dataProvider getTestWithEnvRsyncOptionsProvider
     */
    public function testWithEnvRsyncOptions(string $environment, array $expectedCommand): void
    {
        $config = $this->getDefaultConfig();
        $config['environments'][$environment]['rsync_options'] = [
            'option1',
            'option2',
        ];
        $commandTester = $this->createCommandTester($config, $expectedCommand, true);
        $exitCode = $commandTester->execute([
            'environment' => $environment,
        ]);

        $this->assertSame("line1\nline2\n", $commandTester->getDisplay(true));
        $this->assertSame(0, $exitCode);
    }

    public function getTestWithEnvRsyncOptionsProvider(): \Generator
    {
        yield ['env1', [
            'rsync',
            '--dry-run',
            'option1',
            'option2',
            '--exclude-from='.realpath(__DIR__.'/../ignore_file.txt'),
            '-e',
            'ssh -p22',
            '/local/path/',
            'username1@host1:/path1/',
        ]];
        yield ['env2', [
            'rsync',
            '--dry-run',
            'option1',
            'option2',
            '--exclude-from='.realpath(__DIR__.'/../ignore_file.txt'),
            '-e',
            'ssh -p23',
            '/local/path/',
            'username2@host2:/path2/',
        ]];
        yield ['env3', [
            'rsync',
            '--dry-run',
            'option1',
            'option2',
            '--exclude-from='.realpath(__DIR__.'/../ignore_file.txt'),
            '/local/path/',
            '/path3/subdir3/',
        ]];
    }

    public function testFailure(): void
    {
        $expectedCommand = [
            'rsync',
            '--dry-run',
            '-azC',
            '--force',
            '--delete',
            '--progress',
            '--exclude-from='.realpath(__DIR__.'/../ignore_file.txt'),
            '-e',
            'ssh -p22',
            '/local/path/',
            'username1@host1:/path1/',
        ];
        $commandTester = $this->createCommandTester($this->getDefaultConfig(), $expectedCommand, false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Error during Rsync execution');

        $commandTester->execute([
            'environment' => 'env1',
        ]);
    }

    /**
     * @dataProvider getTestGetDirPathProvider
     */
    public function testGetDirPath(string $dir, string $expected): void
    {
        $class = new \ReflectionClass(DeployRsyncCommand::class);
        $method = $class->getMethod('getDirPath');
        $method->setAccessible(true);
        $command = new DeployRsyncCommand([], [], ''); // @phpstan-ignore-line

        $this->assertSame($expected, $method->invokeArgs($command, [$dir]));
    }

    public function getTestGetDirPathProvider(): array
    {
        return [
            ['dir', 'dir/'],
            ['/dir', '/dir/'],
            ['/dir/', '/dir/'],
            ['dir/dir', 'dir/dir/'],
            ['/dir/dir', '/dir/dir/'],
            ['/dir/dir/', '/dir/dir/'],
        ];
    }

    protected function createCommandTester(array $config, array $expectedCommand, bool $isSuccessful): CommandTester
    {
        $command = $this->getMockBuilder(DeployRsyncCommand::class) // @phpstan-ignore-line
            ->setConstructorArgs([$config['environments'], $config['rsync'], '/local/path'])
            ->onlyMethods(['createProcess'])
            ->getMock();

        if (\count($expectedCommand) > 0) {
            $command->expects($this->once())->method('createProcess')->with($expectedCommand)->willReturnCallback(function ($command) use ($isSuccessful) {
                $process = $this->getMockBuilder(Process::class)
                    ->setConstructorArgs([$command])
                    ->onlyMethods(['start', 'getIterator', 'isSuccessful'])
                    ->getMock();

                $process->expects($this->once())->method('start');
                $process->expects($this->once())->method('getIterator')->willReturnCallback(function () {
                    foreach (['line1', 'line2'] as $line) {
                        yield $line;
                    }
                });
                $process->expects($this->once())->method('isSuccessful')->willReturn($isSuccessful);

                return $process;
            });
        } else {
            $command->expects($this->never())->method('createProcess');
        }

        $command->setName('ecommit:deploy-rsync');

        $application = new Application();
        if (method_exists($application, 'addCommand')) {
            $application->addCommand($command);
        } else { // @legacy SF <= 7.4
            $application->add($command);
        }

        return new CommandTester($application->find('ecommit:deploy-rsync'));
    }

    protected function getDefaultConfig(): array
    {
        return [
            'environments' => [
                'env1' => [
                    'target' => 'ssh://username1@host1:/path1',
                ],
                'env2' => [
                    'target' => 'ssh://username2@host2:23:/path2',
                ],
                'env3' => [
                    'target' => 'file:///path3/subdir3',
                ],
            ],
            'rsync' => [
                'rsync_path' => 'rsync',
                'rsync_options' => ['-azC', '--force', '--delete', '--progress'],
                'ignore_file' => realpath(__DIR__.'/../ignore_file.txt'),
            ],
        ];
    }
}
