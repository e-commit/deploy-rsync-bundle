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

namespace Ecommit\DeployRsyncBundle\DependencyInjection;

use Ecommit\DeployRsyncBundle\Command\DeployRsyncCommand;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @phpstan-type ProcessedConfiguration array{
 *     environments: Environments,
 *     rsync: RsyncConfig,
 * }
 * @phpstan-type Environments array<string, array{target: string, rsync_options?: string[], ignore_file?: string}>
 * @phpstan-type RsyncConfig array{rsync_path: string, rsync_options: string[], ignore_file: ?string}
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('ecommit_deploy_rsync');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('environments')
                    ->useAttributeAsKey('name')
                    ->normalizeKeys(false)
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('target')->isRequired() // @legacy SF < 7.2 (string node introduced in Symfony 7.2)
                                ->validate()
                                    ->ifTrue(fn (mixed $value) => !\is_string($value) || (!preg_match(DeployRsyncCommand::TARGET_FILE_REGEX, $value) && !preg_match(DeployRsyncCommand::TARGET_SSH_REGEX, $value)))
                                    ->thenInvalid('Invalid target')
                                ->end()
                            ->end()
                            ->arrayNode('rsync_options')->prototype('scalar')->end()->end()  // @legacy SF < 7.2 (string node introduced in Symfony 7.2)
                            ->scalarNode('ignore_file') // @legacy SF < 7.2 (string node introduced in Symfony 7.2)
                                ->validate()
                                    ->ifTrue(fn (mixed $value) => !\is_string($value) && null !== $value)
                                    ->thenInvalid('Invalid ignore file')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('rsync')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('rsync_path') // @legacy SF < 7.2 (string node introduced in Symfony 7.2)
                            ->defaultValue('rsync')
                            ->validate()
                                ->ifTrue(fn (mixed $value) => !\is_string($value))
                                ->thenInvalid('Invalid ignore file')
                            ->end()
                        ->end()
                        ->arrayNode('rsync_options')
                            ->prototype('scalar')->end() // @legacy SF < 7.2 (string node introduced in Symfony 7.2)
                            ->defaultValue(['-azC', '--force', '--delete', '--progress'])
                        ->end()
                        ->scalarNode('ignore_file')  // @legacy SF < 7.2 (string node introduced in Symfony 7.2)
                            ->defaultNull()
                            ->validate()
                                ->ifTrue(fn (mixed $value) => !\is_string($value) && null !== $value)
                                ->thenInvalid('Invalid ignore file')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
