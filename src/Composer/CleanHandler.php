<?php
namespace Bankiru\DistributionBundle\Composer;

use Composer\Script\Event;

class CleanHandler extends AbstractHandler
{
    /**
     * @param Event  $event  An Event instance
     *
     * @throws \RuntimeException
     */
    public static function cleanAll(Event $event)
    {
        self::cleanVcsMeta($event);
        self::cleanTests($event);
        self::cleanCustom($event);
    }

    /**
     * @param Event  $event  An Event instance
     *
     * @throws \RuntimeException
     */
    public static function cleanVcsMeta(Event $event)
    {
        $io = $event->getIO();

        $options = self::getOptions($event);

        if ($event->isDevMode() && !$options['clean-in-dev']) {
            $io->write('VCS metadata removing skipped in dev mode');

            return;
        }

        $io->write('VCS metadata removing started');
        $predicates = implode(
            ' -or ',
            array_map(
                function ($pattern) {
                    return "-name '{$pattern}'";
                },
                $options['clean-vcs-meta-patterns']
            )
        );
        $verboseFlag = $io->isVerbose() ? 'v' : '';
        $cmd         = "find . -depth {$predicates} -exec rm -rf{$verboseFlag} '{}' \\;";
        self::runProcess($event, $cmd);
        $io->write('VCS metadata removing finished');
    }

    /**
     * @param Event  $event  An Event instance
     *
     * @throws \RuntimeException
     */
    public static function cleanTests(Event $event)
    {
        $io = $event->getIO();

        $options = self::getOptions($event);

        if ($event->isDevMode() && !$options['clean-in-dev']) {
            $io->write('Tests removing skipped in dev mode');

            return;
        }

        $io->write('Tests removing started');
        $verboseFlag = $io->isVerbose() ? 'v' : '';
        $cmd         = "find . -depth -type d -name tests -exec rm -rf{$verboseFlag} '{}' \\;";
        self::runProcess($event, $cmd);
        $io->write('Tests removing finished');
    }

    /**
     * @param Event  $event  An Event instance
     *
     * @throws \RuntimeException
     */
    public static function cleanCustom(Event $event)
    {
        $io = $event->getIO();

        $options = self::getOptions($event);

        if (empty($options['clean-custom'])) {
            if ($io->isVerbose()) {
                $io->write('Custom files/dirs removing skipped because empty');
            }

            return;
        }

        if ($event->isDevMode() && !$options['clean-in-dev']) {
            $io->write('Custom files/dirs removing skipped in dev mode');

            return;
        }

        $io->write('Custom files/dirs removing started');
        $items       = implode(' ', $options['clean-custom']);
        $verboseFlag = $io->isVerbose() ? 'v' : '';
        $cmd         = "rm -rf{$verboseFlag} {$items}";
        self::runProcess($event, $cmd);
        $io->write('Custom files/dirs removing finished');
    }

    /**
     * @param Event $event
     *
     * @return array
     */
    protected static function getOptions(Event $event)
    {
        $options = array_merge(
            [
                'clean-custom'            => null,
                'clean-in-dev'            => false,
                'clean-vcs-meta-patterns' => ['.git*', '.hg*', '.svn*', '.cvs*'],
            ],
            parent::getOptions($event)
        );

        if (!empty($options['clean-custom'])) {
            if (is_string($options['clean-custom'])) {
                $options['clean-custom'] = [$options['clean-custom']];
            } elseif (!is_array($options['clean-custom'])) {
                throw new \RuntimeException(sprintf(
                    'Invalid value of extra.clean-custom: (%s) %s',
                    gettype($options['clean-custom']),
                    var_export($options['clean-custom'], true)
                ));
            }
        }

        return $options;
    }
}
