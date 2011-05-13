<?php

namespace Genouest\Bundle\SchedulerBundle\Tests;

class TestCase extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        if (!extension_loaded('sge')) {
            $this->markTestSkipped('Sge extension is not available.');
        }
    }
}
