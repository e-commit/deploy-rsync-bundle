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

namespace Ecommit\DeployRsyncBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Process\Process;

final class DeployRsyncCommand extends Command
{
    /**
     * @var array
     */
    protected $environments;

    /**
     * @var array
     */
    protected $rsyncConfig;

    /**
     * @var string
     */
    protected $projetDir;

    public function __construct(array $environments, array $rsyncConfig, string $projetDir)
    {
        $this->environments = $environments;
        $this->rsyncConfig = $rsyncConfig;
        $this->projetDir = $projetDir;

        parent::__construct();
    }

    protected static $defaultName = 'ecommit:deploy-rsync';

    protected static $defaultDescription = 'Deploy the application with RSYNC and SSH';

    protected function configure(): void
    {
        $this
            ->addArgument('environment', InputArgument::REQUIRED, 'Environment name')
            ->addOption('go', null, InputOption::VALUE_NONE, 'Do the deployment')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!\array_key_exists($input->getArgument('environment'), $this->environments)) {
            throw new RuntimeException('Environment not found: '.$input->getArgument('environment'));
        }
        $environment = $this->environments[$input->getArgument('environment')];

        $ignoreFile = $environment['ignore_file'] ?? $this->rsyncConfig['ignore_file'];
        if (!$ignoreFile) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Continue without ignore file? [y/N]', false);

            if (!$helper->ask($input, $output, $question)) {
                return 0;
            }
        } elseif (!file_exists($ignoreFile)) {
            throw new RuntimeException(sprintf('Ignore file "%s" not found', $ignoreFile));
        }

        $command = [$this->rsyncConfig['rsync_path']];
        if (!$input->getOption('go')) {
            $command[] = '--dry-run';
        }
        $rsyncOptions = (isset($environment['rsync_options']) && \count($environment['rsync_options']) > 0) ? $environment['rsync_options'] : $this->rsyncConfig['rsync_options'];
        if ($rsyncOptions) {
            $command = array_merge($command, $rsyncOptions);
        }
        if ($ignoreFile) {
            $command[] = '--exclude-from='.$ignoreFile;
        }
        $command[] = '-e';
        $command[] = sprintf('ssh -p%s', $environment['port']);
        $command[] = $this->getDirPath($this->projetDir);
        $command[] = sprintf('%s@%s:%s', $environment['username'], $environment['hostname'], $this->getDirPath($environment['dir']));

        foreach ($this->executeProcess($command) as $data) {
            $output->writeln($data);
        }

        return 0;
    }

    protected function getDirPath(string $dir): string
    {
        if ('/' !== mb_substr($dir, -1)) {
            return $dir.'/';
        }

        return $dir;
    }

    protected function executeProcess(array $command): \Iterator
    {
        $process = $this->createProcess($command);
        $process->start();

        foreach ($process as $type => $data) {
            yield $data;
        }

        if (!$process->isSuccessful()) {
            throw new RuntimeException('Error during Rsync execution');
        }
    }

    protected function createProcess(array $command): Process
    {
        return new Process($command);
    }
}
