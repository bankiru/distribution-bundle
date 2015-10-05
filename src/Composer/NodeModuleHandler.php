<?php
namespace Bankiru\DistributionBundle\Composer;

use Composer\Script\Event;
use Symfony\Component\Process\Process;

class NodeModuleHandler extends AbstractHandler
{
    /**
     * @param Event $event An Event instance
     *
     * @throws \RuntimeException
     */
    public static function grunt(Event $event)
    {
        if (self::isSkip($event, 'grunt')) {
            return;
        }

        $options = self::getOptions($event);

        if (!file_exists($options['grunt-work-dir'] . DIRECTORY_SEPARATOR . 'Gruntfile.js')) {
            throw new \RuntimeException(sprintf('File "Gruntfile.js" was not found in directory "%s"', $options['grunt-work-dir']));
        }

        $args = $options['grunt-args'][$event->isDevMode() ? 'dev' : 'prod'];

        if (!preg_match('@(^|\s)(-v|--verbose|-d|--debug)(\s|$)@', $args) && $event->getIO()->isVerbose()) {
            if ($event->getIO()->isDebug()) {
                $args .= ' --debug';
            } else {
                $args .= ' --verbose';
            }
            $args = trim($args);
        }

        $process = self::runModule('grunt', $args, $event);

        if ($options['grunt-fail-on-warning']) {
            // костыль для отлова ворнингов
            $warnings = array_filter(explode(PHP_EOL, $process->getOutput()), function ($buffer) {
                return strpos($buffer, "\x1b\x5b\x33\x31\x6d\x3e\x3e\x20") === 0; // две красные угловые стрелки
            });

            if (!empty($warnings)) {
                $warnings = implode(', ', array_map(function ($buffer) { substr($buffer, 8); }, $warnings));

                throw new \RuntimeException('grunt generates warnings: ' . $warnings);
            }
        }
    }

    /**
     * @param Event $event An Event instance
     *
     * @throws \RuntimeException
     */
    public static function gulp(Event $event)
    {
        if (self::isSkip($event, 'gulp')) {
            return;
        }

        $options = self::getOptions($event);

        if (!file_exists($options['gulp-work-dir'] . DIRECTORY_SEPARATOR . 'gulpfile.js')) {
            throw new \RuntimeException(sprintf('File "Gulpfile.js" was not found in directory "%s"', $options['gulp-work-dir']));
        }

        $args = $options['gulp-args'][$event->isDevMode() ? 'dev' : 'prod'];

        $process = self::runModule('gulp', $args, $event);

        if ($options['gulp-fail-on-warning']) {
            // костыль для отлова ворнингов
            $warnings = array_filter(explode(PHP_EOL, $process->getOutput()), function ($buffer) {
                return strpos($buffer, "\x1b\x5b\x33\x31\x6d\x3e\x3e\x20") === 0; // две красные угловые стрелки
            });

            if (!empty($warnings)) {
                $warnings = implode(', ', array_map(function ($buffer) { substr($buffer, 8); }, $warnings));

                throw new \RuntimeException('gulp generates warnings: ' . $warnings);
            }
        }
    }

    /**
     * @param $event Event A instance
     *
     * @throws \RuntimeException
     */
    public static function bowerInstall(Event $event)
    {
        self::bower($event, 'install');
    }

    /**
     * @param Event $event An Event instance
     *
     * @throws \RuntimeException
     */
    public static function bowerUpdate(Event $event)
    {
        self::bower($event, 'install');
    }

    /**
     * @param Event $event An Event instance
     *
     * @throws \RuntimeException
     * @return Process
     *
     */
    private static function bower(Event $event, $args)
    {
        if (self::isSkip($event, 'bower')) {
            return;
        }

        $options = self::getOptions($event);

        if (!file_exists($options['bower-work-dir'] . DIRECTORY_SEPARATOR . 'bower.json')) {
            throw new \RuntimeException(sprintf('File "bower.json" was not found in directory "%s"', $options['bower-work-dir']));
        }

        if (!preg_match('@(^|\s)(-V|--verbose|-s|--silent|-q|--quiet)(\s|$)@', $args) && $event->getIO()->isVerbose()) {
            $args = trim($args . ' --verbose');
        }

        return self::runModule('bower', $args, $event);
    }

    /**
     * @param Event $event An Event instance
     *
     * @return array
     */
    protected static function getOptions(Event $event)
    {
        static $options;

        if ($options === null) {
            $options = array_merge(
                [
                    'node_modules-dir'      => './node_modules',
                    'bower-work-dir'        => '.',
                    'bower-run-condition'   => null,
                    'grunt-work-dir'        => '.',
                    'grunt-run-condition'   => null,
                    'grunt-args'            => ['prod' => 'prod', 'dev' => 'dev'],
                    'grunt-fail-on-warning' => false,
                    'gulp-work-dir'         => '.',
                    'gulp-run-condition'    => null,
                    'gulp-args'             => ['prod' => 'prod', 'dev' => 'dev'],
                    'gulp-fail-on-warning'  => false,
                ],
                parent::getOptions($event)
            );
        }

        return $options;
    }

    /**
     * @param Event $event An Event instance
     *
     * @throws \RuntimeException
     * @return Process|null
     *
     */
    private static function runModule($module, $args = '', Event $event)
    {
        $io = $event->getIO();

        $options = self::getOptions($event);

        if (!is_dir($options['node_modules-dir'])) {
            throw new \RuntimeException(sprintf('"%s" is not directory', $options['node_modules-dir']));
        }

        $io->write(sprintf('Node module %s started with args "%s"', $module, $args));

        if (!is_dir($options[$module . '-work-dir'])) {
            throw new \RuntimeException(sprintf('"%s" is not directory', $options[$module . '-work-dir']));
        }

        $commandline = trim(self::findBin($options['node_modules-dir'], $module) . ' ' . $args);
        $process     = self::runProcess($event, $commandline, $options[$module . '-work-dir']);

        $io->write(sprintf('Node module %s finished', $module));

        return $process;
    }

    /**
     * @param string $nodeModulesDir
     * @param string $module
     *
     * @return string
     */
    private static function findBin($nodeModulesDir, $module)
    {
        $nodeModulesBinDir = $nodeModulesDir . DIRECTORY_SEPARATOR . '.bin';

        $nodeModuleBin = $nodeModulesBinDir . DIRECTORY_SEPARATOR . $module;

        if (!file_exists($nodeModuleBin)) {
            throw new \RuntimeException(sprintf(
                'Can not find node module %s executable in %s',
                $module, $nodeModulesBinDir
            ));
        }

        if (!is_executable($nodeModuleBin)) {
            throw new \RuntimeException(sprintf(
                'Node module %s executable in %s is not executable',
                $module, $nodeModulesBinDir
            ));
        }

        return realpath($nodeModuleBin);
    }

    /**
     * @param Event  $event  An Event instance
     * @param string $module
     *
     * @return bool
     */
    private static function isSkip(Event $event, $module)
    {
        $io = $event->getIO();

        $options = self::getOptions($event);

        if (!empty($options[$module . '-run-condition']) && !self::evaluateCondition($options[$module . '-run-condition'])) {
            $io->write(sprintf('Node module %s skipped because %s-run-condition', $module, $module));

            return true;
        }

        return false;
    }
}
