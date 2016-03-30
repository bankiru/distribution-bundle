<?php

namespace Bankiru\DistributionBundle\Composer\NodeModule;

/**
 * @internal
 */
final class Config
{
    const EXTRA_NODE_MODULES = 'node_modules';
    const EXTRA_WORK_DIR = 'work-dir';
    const EXTRA_ENV = 'env';
    const EXTRA_ARGS = 'args';

    /** @var NodeModule */
    private $module;

    /** @var array */
    private $extra = [];

    /** @var array */
    private $args = [];

    /** @var string */
    private $workingDirectory;

    /** @var string */
    private $nodeModulesDirectory;

    /** @var int */
    private $timeout;

    /** @var array */
    private $env = [];

    /**
     * Config constructor.
     *
     * @param NodeModule $module
     *
     * @throws NodeModuleException
     */
    public function __construct(NodeModule $module)
    {
        $this->module = $module;

        $this->loadExtra();

        $this->resolveTimeout();
        $this->resolveWorkingDirectory();
        $this->resolveNodeModulesDirectory();
    }

    /**
     * @param $module
     *
     * @return self
     *
     * @throws NodeModuleException
     */
    public static function create(NodeModule $module)
    {
        return new self($module);
    }

    /**
     *
     */
    protected function loadExtra()
    {
        $defaultExtra = [
            self::EXTRA_NODE_MODULES => './node_modules',
            self::EXTRA_ENV => [
                'prod' => [
                    'ENVIRONMENT' => 'prod',
                    'NODE_ENV' => 'prod',
                ],
                'dev' => [
                    'ENVIRONMENT' => 'dev',
                    'NODE_ENV' => 'dev',
                ],
            ],
            $this->module->getName() => [
                self::EXTRA_WORK_DIR => './',
            ],
        ];

        $this->extra = array_merge(
            $defaultExtra,
            [$this->module->getName() => $this->module->defaultModuleExtra()],
            $this->module->getEvent()->getComposer()->getPackage()->getExtra()
        );
    }

    /**
     * @return array
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * @return array
     */
    public function getModuleExtra()
    {
        return isset($this->extra[$this->module->getName()])
            ? $this->extra[$this->module->getName()]
            : [];
    }

    protected function resolveTimeout()
    {
        $this->timeout = (int) $this->module->getEvent()->getComposer()->getConfig()->get('process-timeout');
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @param int $timeout
     *
     * @return Config
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * @param bool $splitMode
     *
     * @return $this
     */
    public function resolveArgs($splitMode = false)
    {
        $extra = $this->getModuleExtra();

        if (isset($extra[self::EXTRA_ARGS])) {
            $args = $extra[self::EXTRA_ARGS];

            $mode = $this->module->getEvent()->isDevMode() ? 'dev' : 'prod';

            if ($splitMode && is_array($args) && isset($args[$mode])) {
                $args = $args[$mode];
            }

            $this->setArgs((array) $args);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * @param array $args
     *
     * @return Config
     */
    public function setArgs($args)
    {
        $this->args = $args;

        return $this;
    }

    /**
     * @param bool $splitMode
     *
     * @return $this
     */
    public function resolveEnv($splitMode = false)
    {
        $extra = $this->getModuleExtra();

        $env = null;
        if (isset($extra[self::EXTRA_ENV])) {
            $env = $extra[self::EXTRA_ENV];
        } else {
            $extra = $this->getExtra();

            if (isset($extra[self::EXTRA_ENV])) {
                $env = $extra[self::EXTRA_ENV];
            }
        }

        if ($env !== null) {
            $mode = $this->module->getEvent()->isDevMode() ? 'dev' : 'prod';

            if ($splitMode && is_array($env) && isset($env[$mode])) {
                $env = $env[$mode];
            }

            $this->setEnv((array) $env);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getEnv()
    {
        return $this->env;
    }

    /**
     * @param array $env
     *
     * @return Config
     */
    public function setEnv($env)
    {
        $this->env = $env;

        return $this;
    }

    /**
     * @return $this
     *
     * @throws NodeModuleException
     */
    protected function resolveNodeModulesDirectory()
    {
        if (isset($this->extra[self::EXTRA_NODE_MODULES])) {
            $nodeModulesDirectory = $this->extra[self::EXTRA_NODE_MODULES];

            if (strpos($nodeModulesDirectory, '@'.self::EXTRA_WORK_DIR.'@') !== false) {
                $nodeModulesDirectory = str_replace(
                    '@'.self::EXTRA_WORK_DIR.'@',
                    $this->getWorkingDirectory(),
                    $nodeModulesDirectory
                );
            }

            $this->setNodeModulesDirectory($nodeModulesDirectory);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getNodeModulesDirectory()
    {
        return $this->nodeModulesDirectory;
    }

    /**
     * @param string $nodeModulesDirectory
     *
     * @return $this
     */
    public function setNodeModulesDirectory($nodeModulesDirectory)
    {
        $this->nodeModulesDirectory = $nodeModulesDirectory;

        return $this;
    }

    /**
     * @return $this
     *
     * @throws NodeModuleException
     */
    public function resolveWorkingDirectory()
    {
        $extra = $this->getModuleExtra();

        $this->workingDirectory = isset($extra[self::EXTRA_WORK_DIR])
            ? $extra[self::EXTRA_WORK_DIR]
            : getcwd();

        return $this;
    }

    /**
     * @return string
     */
    public function getWorkingDirectory()
    {
        return $this->workingDirectory;
    }

    /**
     * @param string $workingDirectory
     *
     * @return Config
     */
    public function setWorkingDirectory($workingDirectory)
    {
        $this->workingDirectory = $workingDirectory;

        return $this;
    }

    /**
     * @throws NodeModuleException
     */
    public function validate()
    {
        $errors = [];

        if ($this->timeout === null) {
            $errors[] = '"timeout" does not specified';
        }

        if ($this->workingDirectory === null) {
            $errors[] = '"workingDirectory" does not specified';
        } elseif (!is_dir($this->workingDirectory)) {
            $errors[] = sprintf('workingDirectory "%s" does not exists', $this->workingDirectory);
        }

        if ($this->nodeModulesDirectory === null) {
            $errors[] = '"nodeModulesDirectory" does not specified';
        } elseif (!is_dir($this->nodeModulesDirectory)) {
            $errors[] = sprintf('nodeModulesDirectory "%s" does not exists', $this->nodeModulesDirectory);
        }

        if ($errors) {
            throw new NodeModuleException(implode('; ', $errors));
        }
    }
}
