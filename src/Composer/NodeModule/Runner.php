<?php

namespace Bankiru\DistributionBundle\Composer\NodeModule;

use Composer\IO\IOInterface;
use Symfony\Component\Process\Exception\InvalidArgumentException;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

/**
 * @internal
 */
final class Runner
{
    /** @var NodeModule */
    private $module;

    /** @var IOInterface */
    private $io;

    /** @var Config */
    private $config;

    /**
     * NodeModuleRunner constructor.
     *
     * @param NodeModule $module
     *
     * @throws NodeModuleException
     */
    public function __construct(NodeModule $module)
    {
        $this->module = $module;
    }

    /**
     * NodeModuleRunner factory.
     *
     * @param NodeModule $module
     *
     * @return Runner
     *
     * @throws NodeModuleException
     */
    public static function create(NodeModule $module)
    {
        return new self($module);
    }

    /**
     * @return IOInterface
     */
    public function getIo()
    {
        return $this->io;
    }

    /**
     * @param IOInterface $io
     *
     * @return $this
     */
    public function setIO($io)
    {
        $this->io = $io;

        return $this;
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param Config $config
     *
     * @return Runner
     */
    public function setConfig($config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @return Process
     *
     * @throws NodeModuleException
     */
    public function run()
    {
        $this->config->validate();

        $this->io->write(sprintf('Node module %s started with args "%s"', $this->module->getName(), implode(' ', $this->config->getArgs())));

        $arguments = $this->config->getArgs();
        array_unshift($arguments, $this->findExecutable());

        try {
            $process = ProcessBuilder::create($arguments)
                ->addEnvironmentVariables($this->config->getEnv())
                ->setWorkingDirectory($this->config->getWorkingDirectory())
                ->setTimeout($this->config->getTimeout())
                ->getProcess();

            $process->run(
                function ($type, $buffer) {
                    if ($type === Process::OUT) {
                        $this->io->write($buffer);
                    } elseif ($type === Process::ERR) {
                        $this->io->writeError($buffer);
                    }
                }
            );
        } catch (InvalidArgumentException $exception) {
            throw new NodeModuleException($exception->getMessage(), $exception->getCode(), $exception);
        } catch (LogicException $exception) {
            throw new NodeModuleException($exception->getMessage(), $exception->getCode(), $exception);
        } catch (RuntimeException $exception) {
            throw new NodeModuleException($exception->getMessage(), $exception->getCode(), $exception);
        }

        if (!$process->isSuccessful()) {
            throw new NodeModuleException(
                sprintf(
                    'An error occurred when executing the %s command: %s',
                    escapeshellarg($process->getCommandLine()),
                    PHP_EOL."\t".$process->getErrorOutput()
                )
            );
        }

        $this->io->write(sprintf('Node module %s finished', $this->module));

        return $process;
    }

    /**
     * @return string
     *
     * @throws NodeModuleException
     */
    private function findExecutable()
    {
        $nodeModulesBinDirectory = $this->config->getNodeModulesDirectory().DIRECTORY_SEPARATOR.'.bin';

        $executable = $nodeModulesBinDirectory.DIRECTORY_SEPARATOR.$this->module;

        if (!file_exists($executable)) {
            throw new NodeModuleException(
                sprintf(
                    'Can not find node module %s executable in %s',
                    $this->module,
                    $nodeModulesBinDirectory
                )
            );
        }

        if (!is_executable($executable)) {
            throw new NodeModuleException(
                sprintf(
                    'Node module %s executable in %s is not executable',
                    $this->module,
                    $nodeModulesBinDirectory
                )
            );
        }

        return realpath($executable);
    }
}
