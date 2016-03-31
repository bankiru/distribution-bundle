<?php

namespace Bankiru\DistributionBundle;

use AspectMock\Test as AspectMockTest;

class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @inheritDoc
     */
    protected function tearDown()
    {
        AspectMockTest::clean();
    }
}