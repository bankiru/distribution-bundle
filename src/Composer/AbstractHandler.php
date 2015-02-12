<?php
namespace Bankiru\DistributionBundle\Composer;

use Composer\IO\IOInterface;
use Composer\Script\Event;
use Symfony\Component\Process\Process;

abstract class AbstractHandler
{
    /**
     * @param Event $event
     * @return array
     */
    protected static function getOptions(Event $event)
    {
        $options = array_merge(
            [
            ],
            $event->getComposer()->getPackage()->getExtra()
        );

        $options['process-timeout'] = $event->getComposer()->getConfig()->get('process-timeout');

        return $options;
    }

    /**
     * @param IOInterface $io
     * @param $commandline
     * @param null $cwd
     * @param array $env
     * @param null $input
     * @param int $timeout
     * @param array $options
     * @return Process
     */
    protected static function runProcess(
        IOInterface $io,
        $commandline,
        $cwd = null,
        array $env = null,
        $input = null,
        $timeout = 60,
        array $options = []
    ) {
        if ($io->isDebug()) {
            $io->write(sprintf('Executing command: %s', escapeshellarg($commandline)));
        }

        $process = new Process($commandline, $cwd, $env, $input, $timeout, $options);
        $process->run(function ($type, $buffer) use ($io) {
            if ($type == Process::OUT) {
                $io->write($buffer);
            }
        });
        if (!$process->isSuccessful()) {
            throw new \RuntimeException(sprintf(
                'An error occurred when executing the %s command: %s',
                escapeshellarg($commandline),
                PHP_EOL . "\t" . $process->getErrorOutput()
            ));
        }
        return $process;
    }
}