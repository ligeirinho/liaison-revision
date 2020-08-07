<?php

/**
 * This file is part of Liaison Revision.
 *
 * (c) John Paul E. Balandan, CPA <paulbalandan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Liaison\Revision\Upgrade;

use CodeIgniter\CLI\CLI;
use Liaison\Revision\Config\ConfigurationResolver;
use Liaison\Revision\Exception\RevisionException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Upgrader for Composer-installed projects
 */
class ComposerUpgrader implements UpgraderInterface
{
    /**
     * {@inheritDoc}
     */
    public function upgrade(string $rootPath): int
    {
        $composer = $this->findComposerPhar();

        $cmd     = "$composer update --ansi";
        $process = Process::fromShellCommandline($cmd, $rootPath, null, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            try {
                $process->setTty(true);
            } catch (\RuntimeException $e) {
                CLI::write('Warning: ' . $e->getMessage(), 'yellow'); // @codeCoverageIgnore
            }
        }

        try {
            $process->mustRun(function ($type, $line) {
                CLI::print($line);
            });
        } catch (\Throwable $e) {
            throw new RevisionException($e->getMessage());
        }

        return EXIT_SUCCESS;
    }

    /**
     * Gets the path of the composer executable.
     *
     * @throws RevisionException
     * @return string
     */
    protected function findComposerPhar(): string
    {
        $phpBinary      = (string) (new PhpExecutableFinder())->find();
        $composerLocal  = rtrim((new ConfigurationResolver())->rootPath, '\\/ ') . DIRECTORY_SEPARATOR;

        if (is_file($composerLocal . 'composer.phar')) {
            // @codeCoverageIgnoreStart
            return sprintf('%s %s', escapeshellarg($phpBinary), escapeshellarg($composerLocal . 'composer.phar'));
            // @codeCoverageIgnoreEnd
        }

        if (null === (new ExecutableFinder())->find('composer')) {
            // @codeCoverageIgnoreStart
            throw new RevisionException(lang('Revision.incompatibleUpgraderHandler', [static::class, 'No composer executable found.']));
            // @codeCoverageIgnoreEnd
        }

        return 'composer';
    }
}