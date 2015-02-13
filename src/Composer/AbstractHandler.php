<?php
namespace Bankiru\DistributionBundle\Composer;

use Composer\Script\Event;
use Symfony\Component\Process\Process;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

abstract class AbstractHandler
{
    /**
     * @param Event $event
     * @return array
     */
    protected static function getOptions(Event $event)
    {
        static $options;

        if ($options === null) {
            $options = array_merge(
                [
                ],
                $event->getComposer()->getPackage()->getExtra()
            );

            $options['process-timeout'] = $event->getComposer()->getConfig()->get('process-timeout');
        }

        return $options;
    }

    /**
     * @param Event $event
     * @param $commandline
     * @param null $cwd
     * @param array $env
     * @param null $input
     * @param int $timeout
     * @param array $options
     * @return Process
     */
    protected static function runProcess(
        Event $event,
        $commandline,
        $cwd = null,
        array $env = null,
        $input = null,
        $timeout = null,
        array $options = []
    ) {
        if ($timeout === null) {
            $timeout = self::getOptions($event)['process-timeout'];
        }

        $io = $event->getIO();

        if ($io->isDebug()) {
            $io->write(sprintf('Executing command: %s', escapeshellarg($commandline)));
        }

        $hideOutput = !empty($options['hide-output']);
        unset($options['hide-output']);

        $process = new Process($commandline, $cwd, $env, $input, $timeout, $options);
        $process->run(function ($type, $buffer) use ($io, $hideOutput) {
            if ($type == Process::OUT && !$hideOutput) {
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

    protected static function evaluateCondition($condition)
    {
        $expressionLanguage = new ExpressionLanguage();

        $expressionLanguage->register(
            'getenv',
            function ($envvar) {
                return sprintf('(is_string(%1$s) ? getenv(%1$s) : false)', $envvar);
            },
            function ($arguments, $envvar) {
                return is_string($envvar) ? getenv($envvar) : false;
            }
        );

        return $expressionLanguage->evaluate($condition);
    }
}