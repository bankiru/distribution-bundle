<?php

namespace Bankiru\DistributionBundle\Composer\NodeModule;

use Bankiru\DistributionBundle\TestCase;
use AspectMock\Test as test;

class ConfigTest extends TestCase
{

    /**
     * @covers Bankiru\DistributionBundle\Composer\NodeModule\Config::create
     */
    public function testCreate()
    {
        $module = $this->getMockBuilder('Bankiru\DistributionBundle\Composer\NodeModule\NodeModule')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass()
        ;

        $config = Config::create($module);

        static::assertInstanceOf('Bankiru\DistributionBundle\Composer\NodeModule\Config', $config);
    }
}
