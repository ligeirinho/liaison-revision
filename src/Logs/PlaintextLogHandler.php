<?php

/**
 * This file is part of Liaison Revision.
 *
 * (c) 2020 John Paul E. Balandan, CPA <paulbalandan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Liaison\Revision\Logs;

use Liaison\Revision\Application;
use Liaison\Revision\Config\Revision;
use Symfony\Component\Filesystem\Filesystem;

/**
 * PlaintextLogHandler.
 */
final class PlaintextLogHandler extends AbstractLogHandler
{
    /**
     * Buffer to write to log file.
     *
     * @var string
     */
    public $buffer = '';

    /**
     * Constructor.
     *
     * @param null|\Liaison\Revision\Config\Revision        $config
     * @param null|\Symfony\Component\Filesystem\Filesystem $filesystem
     * @param string                                        $directory
     * @param string                                        $filename
     * @param string                                        $extension
     */
    public function __construct(
        ?Revision $config = null,
        ?Filesystem $filesystem = null,
        string $directory = 'log',
        string $filename = 'revision_',
        string $extension = '.log'
    ) {
        $config     = $config ?? config('Revision');
        $filesystem = $filesystem ?? new Filesystem();
        parent::__construct($config, $filesystem, $directory, $filename, $extension);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize()
    {
        $name    = Application::NAME;
        $version = str_pad(Application::VERSION, 45);
        $date    = str_pad(sprintf('%s UTC%s', date('D, d F Y, H:i:s'), date('P')), 44);

        // Headers
        $this->buffer = <<<EOD
            +========================================================+
            | {$name}                                       |
            | Version: {$version} |
            | Run Date: {$date} |
            +========================================================+

            EOD;

        // Settings
        $config = \get_class($this->config);
        $dirs   = \count($this->config->ignoreDirs);
        $files  = \count($this->config->ignoreFiles);
        $allow  = $this->config->allowGitIgnoreEntry ? lang('Revision.accessAllowed') : lang('Revision.accessDenied');
        $fall   = $this->config->fallThroughToProject ? lang('Revision.accessAllowed') : lang('Revision.accessDenied');
        $logs   = \count($this->config->logHandlers);

        // Labels
        $loadedConfig = lang('Revision.loadedConfigLabel');
        $configLabel  = lang('Revision.configurationClassLabel');
        $rootLabel    = lang('Revision.rootPathLabel');
        $writeLabel   = lang('Revision.writePathLabel');
        $dirsLabel    = lang('Revision.ignoredDirCount');
        $filesLabel   = lang('Revision.ignoredFileCount');
        $allowLabel   = lang('Revision.allowGitignoreLabel');
        $fallLabel    = lang('Revision.fallThroughToProjectLabel');
        $retriesLabel = lang('Revision.maximumRetriesLabel');
        $consolidator = lang('Revision.consolidatorLabel');
        $upgrader     = lang('Revision.upgraderLabel');
        $pathfinder   = lang('Revision.pathfinderLabel');
        $diffLabel    = lang('Revision.diffOutputBuilderLabel');
        $logsLabel    = lang('Revision.logHandlersCount');

        $settings = <<<EOD
            {$configLabel}: {$config}
            {$rootLabel}: {$this->config->rootPath}
            {$writeLabel}: {$this->config->writePath}
            {$dirsLabel}: {$dirs}
            {$filesLabel}: {$files}
            {$allowLabel}: {$allow}
            {$fallLabel}: {$fall}
            {$retriesLabel}: {$this->config->retries}
            {$consolidator}: {$this->config->consolidator}
            {$upgrader}: {$this->config->upgrader}
            {$pathfinder}: {$this->config->pathfinder}
            {$diffLabel}: {$this->config->diffOutputBuilder}
            {$logsLabel}: {$logs}
            \n
            EOD;

        $this->buffer .= "\n{$loadedConfig}\n";
        $this->buffer .= str_repeat('=', mb_strlen($loadedConfig)) . "\n";
        $this->buffer .= $settings;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(string $level, string $message): int
    {
        $this->buffer .= '[' . date('Y-m-d H:i:s') . '] ' . mb_strtoupper($level) . ' -- ' . $message . "\n";

        return LogHandlerInterface::EXIT_SUCCESS;
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
        $buffer       = $this->buffer;
        $this->buffer = '';

        $this->filesystem->dumpFile(
            $this->directory . $this->filename . $this->extension,
            $buffer
        );
    }
}
