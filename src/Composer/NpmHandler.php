<?php
namespace Bankiru\DistributionBundle\Composer;

use Composer\Script\Event;
use Symfony\Component\Process\Process;

class NpmHandler extends AbstractHandler
{
    /**
     * @param $event Event A instance
     * @throws \RuntimeException
     */
    public static function install(Event $event)
    {
        self::runCommand('install', '', $event);
    }

    /**
     * @param $event Event A instance
     * @throws \RuntimeException
     */
    public static function update(Event $event)
    {
        self::runCommand('update', '', $event);
    }

    /**
     * @param Event $event
     * @return array
     */
    protected static function getOptions(Event $event)
    {
        $options = array_merge(
            [
                'npm-work-dir'      => '.',
                'npm-run-condition' => null,
            ],
            parent::getOptions($event)
        );

        if (!file_exists($options['npm-work-dir'] . DIRECTORY_SEPARATOR . 'package.json')) {
            throw new \RuntimeException(sprintf('File "package.json" was not found in the "%s"',
                $options['npm-work-dir']));
        }

        return $options;
    }

    /**
     * @param string $command npm command name
     * @param string $args npm command arguments
     * @param $event Event A instance
     * @return Process|null
     * @throws \RuntimeException
     */
    private static function runCommand($command, $args = '', Event $event)
    {
        $io = $event->getIO();

        $options = self::getOptions($event);

        if (!empty($options['npm-run-condition']) && !self::evaluateCondition($options['npm-run-condition'])) {
            $io->write(sprintf('NPM %s skipped because npm-run-condition', $command));
            return null;
        }

        if (!preg_match('@(^|\s)(-d{1,3}|--verbose|--loglevel|-s|--silent|-q|--quiet)(\s|$)@', $args) && $io->isVerbose()) {
            $args .= ' -';
            switch (true) {
                case $io->isVerbose():
                    $args .= 'd';
                case $io->isVeryVerbose():
                    $args .= 'd';
                case $io->isDebug():
                    $args .= 'd';
            }
            $args = trim($args);
        }

        $io->write(sprintf('NPM %s started', $command));

        $commandline = trim(self::findNpmBin($event) . ' ' . $command . ' ' . $args);
        $process = self::runProcess($event, $commandline, $options['npm-work-dir']);

        $io->write(sprintf('NPM %s finished', $command));

        return $process;
    }

    private static function findNpmBin(Event $event)
    {
        try {
            $process = self::runProcess($event, 'which npm', getcwd(), null, null, null, ['hide-output' => true]);
            return trim($process->getOutput());
        } catch (\RuntimeException $ex) {
            throw new \RuntimeException('Can not find NPM binary: ' . $ex->getMessage());
        }
    }

}