<?php

/*
 * Copyright 2011 Anthony Bretaudeau <abretaud@irisa.fr>
 *
 * Licensed under the CeCILL License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.cecill.info/licences/Licence_CeCILL_V2-en.txt
 *
 */

namespace Genouest\Bundle\SchedulerBundle\Tests\Entity;

use Genouest\Bundle\SchedulerBundle\Tests\TestCase;
use Genouest\Bundle\SchedulerBundle\Entity\Job;

class JobTest extends TestCase
{
    public function testTimestampable()
    {
        $job = new Job();
        $now = new \DateTime("now");

        $this->assertEquals($now, $job->getCreatedAt(), '->getCreatedAt() is filled when creating new Job');
        $this->assertEquals($now, $job->getUpdatedAt(), '->getUpdatedAt() is filled when creating new Job');
    }

    public function testJobUid()
    {
        $job = new Job();
        
        $this->assertEmpty($job->getJobUid(), '->getJobUid() returns nothing if not generated');
        
        $generated = $job->generateJobUid();
        $this->assertRegExp("/^[0-9a-f]*-[\\d]{8}$/", $generated, '->generateJobUid() works with empty Job');
        $this->assertEquals($generated, $job->getJobUid(), '->getJobUid() returns the generated jobuid');
        
        $this->assertNotEquals($generated, $job->generateJobUid(), '->generateJobUid() is random');
        
        $job->setProgramName('test');
        $this->assertRegExp("/^test-[0-9a-f]*-[\\d]{8}$/", $job->generateJobUid(), '->generateJobUid() uses the program name');
    }
    
    public function testLaunchable()
    {
        $job = new Job();
        
        $this->assertFalse($job->isLaunched(), 'New job is not launched');
        $this->assertTrue($job->canBeLaunched(), 'New job can be launched');
        $this->assertFalse($job->isValid(), 'New job is not valid');
        
        $job->setSchedulerJobId('10');
        $this->assertFalse($job->isLaunched(), 'Job with only scheduler id, but no command is not launched');
        $this->assertTrue($job->canBeLaunched(), 'Job with only scheduler id can be launched');
        $this->assertFalse($job->isValid(), 'Job with only scheduler id is not valid');
        
        $job->setSchedulerJobId("");
        $job->generateJobUid();
        $this->assertFalse($job->isLaunched(), 'Job with only uid is not launched');
        $this->assertTrue($job->canBeLaunched(), 'Job with only uid can be launched');
        $this->assertFalse($job->isValid(), 'Job with only uid is not valid');
        
        $job->generateJobUid();
        $job->setSchedulerJobId('10');
        $this->assertFalse($job->isLaunched(), 'Job with jobUid and scheduler id, but no command is not launched');
        $this->assertFalse($job->canBeLaunched(), 'Job with jobUid and scheduler id can be launched');
        $this->assertTrue($job->isValid(), 'Job with jobUid and scheduler id is valid');
    }
    
    public function testCommand()
    {
        $job = new Job();
        
        $job->generateJobUid();
        $job->setSchedulerJobId('10');
        $job->setCommand('test this');
        $this->assertTrue($job->isLaunched(), 'Job with command, jobUid and scheduler id is launched');
        $this->assertFalse($job->canBeLaunched(), 'Job with command, jobUid and scheduler id can be relaunched');
        $this->assertTrue($job->isValid(), 'Job with jobUid and scheduler id is valid');
    }
    
    public function testMail()
    {
        $job = new Job();
        
        $this->assertFalse($job->checkMail(''), 'Empty email is not ok');
        $this->assertTrue($job->checkMail('test@example.org'), 'Valid email is ok');
        $this->assertFalse($job->checkMail('test@exampl e.org'), 'space not allowed in email');
        $this->assertFalse($job->checkMail('testexample.org'), '@ needed in email is ok');
        $this->assertFalse($job->checkMail('test@example.;org'), 'strange chars not allowed in email');
        
        $this->assertFalse($job->hasValidEmail(), 'Job without email');
        $this->assertFalse($job->setEmailContent("test", "test2"), 'Job without email cannot accept email content');
        $this->assertNotEquals('test', $job->getMailSubject(), 'Email subject not saved');
        $this->assertNotEquals('test2', $job->getMailBody(), 'Email body not saved');
        
        $job->setEmail('test@example.org');
        $this->assertTrue($job->hasValidEmail(), 'Job with correct email');
        $this->assertTrue($job->setEmailContent('test', 'test2'), 'Job with valid email accepts email content');
        $this->assertEquals('test', $job->getMailSubject(), 'Email subject saved');
        $this->assertEquals('test2', $job->getMailBody(), 'Email body saved');
    }
}
