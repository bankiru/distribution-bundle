<?php

namespace Bankiru\DistributionBundle\Composer\NodeModule;

use Symfony\Component\Process\Exception\LogicException;

final class Gulp extends NodeModule
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'gulp';
    }

    /**
     * {@inheritdoc}
     */
    public function defaultModuleExtra()
    {
        return [
            'args' => ['prod' => 'prod', 'dev' => 'dev'],
            'fail-on-warning' => true,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $config = Config::create($this)
            ->resolveArgs(true)
            ->resolveEnv(true)
        ;

        if (!file_exists($config->getWorkingDirectory().DIRECTORY_SEPARATOR.'gulpfile.js')) {
            throw new NodeModuleException(
                sprintf('File "gulpfile.js" was not found in directory "%s"', $config->getWorkingDirectory())
            );
        }

        $process = $this->getRunner()
            ->setConfig($config)
            ->run();

        $extra = $config->getModuleExtra();

        if (!empty($extra['fail-on-warning'])) {
            try {
                $this->validateOutput($process->getOutput());
            } catch (LogicException $exception) {
                throw new NodeModuleException($exception->getMessage(), $exception->getCode(), $exception);
            }
        }
    }

    /**
     * @param string $processOutput
     *
     * @throws NodeModuleException
     */
    private function validateOutput($processOutput)
    {
        $warnings = array_filter(
            explode(PHP_EOL, $processOutput),
            function ($buffer) {
                return strpos($buffer, "\x1b\x5b\x33\x31\x6d\x3e\x3e\x20") === 0; // две красные угловые стрелки
            }
        );

        if (0 !== count($warnings)) {
            $warnings = implode(
                ', ',
                array_map(
                    function ($buffer) {
                        return substr($buffer, 8);
                    },
                    $warnings
                )
            );

            throw new NodeModuleException('gulp generates warnings: '.$warnings);
        }
    }
}
