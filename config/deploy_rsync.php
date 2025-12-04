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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Ecommit\DeployRsyncBundle\Command\DeployRsyncCommand;

return static function (ContainerConfigurator $container): void {
    $container->services()

        ->set('ecommit_deploy_rsync.command.deploy_rsync', DeployRsyncCommand::class)
        ->args([param('ecommit_deploy_rsync.environments'), param('ecommit_deploy_rsync.rsync'), param('kernel.project_dir')])
        ->tag('console.command')
    ;
};
