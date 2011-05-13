<?php

namespace Genouest\Bundle\SchedulerBundle\Tests\Scheduler;

use Genouest\Bundle\SchedulerBundle\Tests\TestCase;
use Genouest\Bundle\SchedulerBundle\Scheduler\DrmaaScheduler;
use Genouest\Bundle\SchedulerBundle\Entity\Job;

class DrmaaSchedulerTest extends TestCase
{
    public function testConfigLoad()
    {
        $job = new Job();
        $uid = $job->generateJobUid();
        
        $scheduler = new DrmaaScheduler("/shared/", "/work/", "http://resurl/", "mailbin", "author", "author@example.org");
        $this->assertEquals('/shared/'.$uid, $scheduler->getResultDir($job), 'Correct shared dir');
        
        $scheduler = new DrmaaScheduler("/shared", "/work", "http://resurl", "mailbin", "author", "author@example.org");
    }

}
