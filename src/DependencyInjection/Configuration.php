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

class Configuration implements ConfigurationInterface
{
    /**
     * @psalm-suppress UndefinedMethod
     */
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
                            ->scalarNode('target')->isRequired()
                                ->validate()
                                    ->ifTrue(fn (mixed $value) => !\is_string($value) || (!preg_match(DeployRsyncCommand::TARGET_FILE_REGEX, $value) && !preg_match(DeployRsyncCommand::TARGET_SSH_REGEX, $value)))
                                    ->thenInvalid('Invalid target')
                                ->end()
                            ->end()
                            ->arrayNode('rsync_options')->prototype('scalar')->end()->end()
                            ->scalarNode('ignore_file')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('rsync')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('rsync_path')->defaultValue('rsync')->end()
                        ->arrayNode('rsync_options')
                            ->prototype('scalar')->end()
                            ->defaultValue(['-azC', '--force', '--delete', '--progress'])
                        ->end()
                        ->scalarNode('ignore_file')->defaultNull()->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
