<?php

namespace Bankiru\DistributionBundle\Composer\NodeModule;

use Composer\Script\Event;

abstract class NodeModule
{
    /** @var Event */
    private $event;

    public static function run(Event $event)
    {
        (new static($event))->execute();
    }

    /**
     * NodeModule constructor.
     *
     * @param Event $event
     *
     * @throws NodeModuleException
     */
    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    /**
     * @return string
     */
    abstract public function getName();

    /**
     * @return array
     */
    public function defaultModuleExtra()
    {
        return [];
    }

    /**
     * @throws NodeModuleException
     */
    abstract protected function execute();

    /**
     * @return Event
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @return Runner
     *
     * @throws NodeModuleException
     */
    protected function getRunner()
    {
        return Runner::create($this)
            ->setIO($this->getEvent()->getIO());
    }
}
