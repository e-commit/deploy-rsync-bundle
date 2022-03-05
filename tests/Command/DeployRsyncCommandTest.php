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
    public function testSuccess(): void
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

        $commandTester = $this->createCommandTester($this->getDefaultConfig(), $expectedCommand, true);
        $exitCode = $commandTester->execute([
            'environment' => 'env1',
        ]);

        $this->assertSame("line1\nline2\n", $commandTester->getDisplay(true));
        $this->assertSame(0, $exitCode);
    }

    public function testSuccessGo(): void
    {
        $expectedCommand = [
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
        ];

        $commandTester = $this->createCommandTester($this->getDefaultConfig(), $expectedCommand, true);
        $exitCode = $commandTester->execute([
            'environment' => 'env1',
            '--go' => true,
        ]);

        $this->assertSame("line1\nline2\n", $commandTester->getDisplay(true));
        $this->assertSame(0, $exitCode);
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

    public function testSuccessWithoutIgnoreFile(): void
    {
        $expectedCommand = [
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
        ];

        $config = $this->getDefaultConfig();
        $config['rsync']['ignore_file'] = null;
        $commandTester = $this->createCommandTester($config, $expectedCommand, true);
        $commandTester->setInputs(['y']);
        $exitCode = $commandTester->execute([
            'environment' => 'env1',
        ]);

        $this->assertSame("Continue without ignore file? [y/N]line1\nline2\n", $commandTester->getDisplay(true));
        $this->assertSame(0, $exitCode);
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

    public function testWithGlobalIgnoreFileAndWithEnvIgnoreFile(): void
    {
        $expectedCommand = [
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
        ];

        $config = $this->getDefaultConfig();
        $config['environments']['env1']['ignore_file'] = realpath(__DIR__.'/../ignore_local_file.txt');
        $commandTester = $this->createCommandTester($config, $expectedCommand, true);
        $exitCode = $commandTester->execute([
            'environment' => 'env1',
        ]);

        $this->assertSame("line1\nline2\n", $commandTester->getDisplay(true));
        $this->assertSame(0, $exitCode);
    }

    public function testWithoutGlobalIgnoreFileAndWithEnvIgnoreFile(): void
    {
        $expectedCommand = [
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
        ];

        $config = $this->getDefaultConfig();
        $config['rsync']['ignore_file'] = null;
        $config['environments']['env1']['ignore_file'] = realpath(__DIR__.'/../ignore_local_file.txt');
        $commandTester = $this->createCommandTester($config, $expectedCommand, true);
        $exitCode = $commandTester->execute([
            'environment' => 'env1',
        ]);

        $this->assertSame("line1\nline2\n", $commandTester->getDisplay(true));
        $this->assertSame(0, $exitCode);
    }

    public function testWithEnvRsyncOptions(): void
    {
        $expectedCommand = [
            'rsync',
            '--dry-run',
            'option1',
            'option2',
            '--exclude-from='.realpath(__DIR__.'/../ignore_file.txt'),
            '-e',
            'ssh -p22',
            '/local/path/',
            'username1@host1:/path1/',
        ];

        $config = $this->getDefaultConfig();
        $config['environments']['env1']['rsync_options'] = [
            'option1',
            'option2',
        ];
        $commandTester = $this->createCommandTester($config, $expectedCommand, true);
        $exitCode = $commandTester->execute([
            'environment' => 'env1',
        ]);

        $this->assertSame("line1\nline2\n", $commandTester->getDisplay(true));
        $this->assertSame(0, $exitCode);
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
        $command = new DeployRsyncCommand([], [], '');

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

    protected function createCommandTester(array $config, array $expectedCommand, $isSuccessful): CommandTester
    {
        $command = $this->getMockBuilder(DeployRsyncCommand::class)
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

        $command->setName(DeployRsyncCommand::getDefaultName());

        $application = new Application();
        $application->add($command);

        return new CommandTester($application->find('ecommit:deploy-rsync'));
    }

    protected function getDefaultConfig(): array
    {
        return [
            'environments' => [
                'env1' => [
                    'hostname' => 'host1',
                    'username' => 'username1',
                    'dir' => '/path1',
                    'port' => 22,
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
