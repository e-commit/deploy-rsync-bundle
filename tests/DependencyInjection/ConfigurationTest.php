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

namespace Ecommit\EcommitDeployRsyncBundle\Tests\DependencyInjection;

use Ecommit\DeployRsyncBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    public function testMiniConfig(): void
    {
        $configuration = $this->processConfiguration([]);
        $expected = [
            'environments' => [],
            'rsync' => [
                'rsync_path' => 'rsync',
                'rsync_options' => ['-azC', '--force', '--delete', '--progress'],
                'ignore_file' => null,
            ],
        ];

        $this->assertSame($expected, $configuration);
    }

    public function testMiniConfigWithEnvironments(): void
    {
        $configuration = $this->processConfiguration([
            'environments' => [
                'env1' => [
                    'target' => 'ssh://username1@host1:/path1',
                ],
                'env2' => [
                    'target' => 'file:///path2',
                ],
            ],
        ]);
        $expected = [
            'environments' => [
                'env1' => [
                    'target' => 'ssh://username1@host1:/path1',
                    'rsync_options' => [],
                ],
                'env2' => [
                    'target' => 'file:///path2',
                    'rsync_options' => [],
                ],
            ],
            'rsync' => [
                'rsync_path' => 'rsync',
                'rsync_options' => ['-azC', '--force', '--delete', '--progress'],
                'ignore_file' => null,
            ],
        ];

        $this->assertEquals($expected, $configuration);
    }

    public function testMissingEnvironmentTarget(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/target.+must be configured/');
        $this->processConfiguration([
            'environments' => [
                'env1' => [],
            ],
        ]);
    }

    public function testBadEnvironmentTarget(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/Invalid target/');
        $this->processConfiguration([
            'environments' => [
                'env1' => [
                    'target' => 'fake://hello',
                ],
            ],
        ]);
    }

    protected function processConfiguration(array $configs): array
    {
        $processor = new Processor();

        return $processor->processConfiguration(new Configuration(), ['ecommit_messenger_supervisor' => $configs]);
    }
}
