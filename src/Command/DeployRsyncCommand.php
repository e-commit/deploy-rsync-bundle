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

use Ecommit\DeployRsyncBundle\DependencyInjection\Configuration;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Process\Process;

/**
 * @phpstan-import-type Environments from Configuration
 * @phpstan-import-type RsyncConfig from Configuration
 */
#[AsCommand(name: 'ecommit:deploy-rsync', description: 'Deploy the application with RSYNC and SSH')]
final class DeployRsyncCommand extends Command
{
    public const TARGET_FILE_REGEX = '/^file:\/\/(?<path>.+)$/';
    public const TARGET_SSH_REGEX = '/^ssh:\/\/(?<username>.+)@(?<hostname>[^:]+)(:(?<port>\d+)){0,1}:(?<path>.+)$/';

    /**
     * @param Environments $environments
     * @param RsyncConfig  $rsyncConfig
     */
    public function __construct(protected array $environments, protected array $rsyncConfig, protected string $projetDir)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('environment', InputArgument::REQUIRED, 'Environment name')
            ->addOption('go', null, InputOption::VALUE_NONE, 'Do the deployment')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $environmentName */
        $environmentName = $input->getArgument('environment');
        if (!\array_key_exists($environmentName, $this->environments)) {
            throw new RuntimeException('Environment not found: '.$environmentName);
        }
        $environment = $this->environments[$environmentName];

        $ignoreFile = $environment['ignore_file'] ?? $this->rsyncConfig['ignore_file'];
        if (!$ignoreFile) {
            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Continue without ignore file? [y/N]', false);

            if (!$helper->ask($input, $output, $question)) {
                return 0;
            }
        } elseif (!file_exists($ignoreFile)) {
            throw new RuntimeException(\sprintf('Ignore file "%s" not found', $ignoreFile));
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
        if (preg_match(self::TARGET_FILE_REGEX, $environment['target'], $targetParts)) {
            $command[] = $this->getDirPath($this->projetDir);
            $command[] = $this->getDirPath($targetParts['path']);
        } elseif (preg_match(self::TARGET_SSH_REGEX, $environment['target'], $targetParts)) {
            $command[] = '-e';
            $command[] = \sprintf('ssh -p%s', ('' !== $targetParts['port']) ? $targetParts['port'] : 22);
            $command[] = $this->getDirPath($this->projetDir);
            $command[] = \sprintf('%s@%s:%s', $targetParts['username'], $targetParts['hostname'], $this->getDirPath($targetParts['path']));
        } else {
            throw new \Exception('Invalid target');
        }

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

    /**
     * @param string[] $command
     *
     * @return \Generator<array-key, string>
     */
    protected function executeProcess(array $command): \Generator
    {
        $process = $this->createProcess($command);
        $process->start();

        /** @var string $data */
        foreach ($process as $data) {
            yield $data;
        }

        if (!$process->isSuccessful()) {
            throw new RuntimeException('Error during Rsync execution');
        }
    }

    /**
     * @param string[] $command
     */
    protected function createProcess(array $command): Process
    {
        return new Process($command);
    }
}
