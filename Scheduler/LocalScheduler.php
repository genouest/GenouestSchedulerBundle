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

namespace Genouest\Bundle\SchedulerBundle\Scheduler;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Genouest\Bundle\SchedulerBundle\Exception\InvalidJobException;
use Genouest\Bundle\SchedulerBundle\Entity\Job;

class LocalScheduler implements SchedulerInterface {

    protected $textJobState = array(
        0 => 'running',
        1 => 'finished',
        );

    protected $container;
    
    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    /**
     * Generate shell script to launch the job and launches it.
     * Note that the Job object will get modified at the end of this function.
     *
     * @param Genouest\Bundle\SchedulerBundle\Entity\Job A job object (this object is modified by this function).
     * @returns Genouest\Bundle\SchedulerBundle\Entity\Job A launched Job object
     */
    public function execute(Job $job) {
        
        if (!$job->canBeLaunched()) {
            // Cannot launch the job
            throw new InvalidJobException('Server side problem: no uid or command defined.');
        }
        
        // Generated job files
        
        // program = generateUid().".sh"
        // write command in program file, add email sending if required
        // Run the script and return jobUid
        $workDir = $this->getWorkDir($job);
        $lockFileName = $workDir.$job->getJobUid().".lock";
        $script = $this->container->get('templating')->render('GenouestSchedulerBundle:Scheduler:script_local.sh.twig', array('job' => $job,
            'workDir' => $workDir,
            'lockFileName' => $lockFileName,
            'mailBody' => str_replace("\n","\\n",str_replace("'", "_", $job->getMailBody())),
            'mailBin' => $this->container->getParameter('scheduler.mail_bin'),
            'mailSubject' => str_replace("'", "_", $job->getMailSubject()),
            'fromEmail' => $this->container->getParameter('scheduler.from_email'),
            ));
        
        // Create sh script
        $jobFileName = $workDir.$job->getJobUid().".sh";
        $jobFile = fopen($jobFileName, 'w');
        $fError = $jobFile === false;
        if ($jobFile) {
            $fError = $fError || (false === @fwrite($jobFile, $script));
            $fError = $fError || (false === @fclose($jobFile));
            $fError = $fError || (false === @chmod($jobFileName, 0755));
        }

        if ($fError) {
            $error = error_get_last();
            throw new FileException(sprintf('Could not create file %s (%s)', $jobFileName, strip_tags($error['message'])));
        }
        
        // Create lock file
        // Status watching is as follow:
        // When launching a job, we create a .lock file in work dir. This file is erased when the job is completed.
        // We could store the PID in the database and use ps to see if it's running, but if the same pid is reused
        // by another process after the job is finished, we'll think the job is still running while it is already finished.
        $lockFile = touch($lockFileName);
        if ($lockFile === false) {
            $error = error_get_last();
            throw new FileException(sprintf('Could not create lock file %s (%s)', $lockFileName, strip_tags($error['message'])));
        }
        
        exec("nohup $jobFileName > /dev/null 2> /dev/null < /dev/null&"); // FIXME Execute detached
        
        $job->setSchedulerJobId($lockFileName); // This must to be set when the job is launched.
        
        return $job;
    }

    /**
     * Get the job status
     *
     * @param Genouest\Bundle\SchedulerBundle\Entity\Job A job object
     * @returns int Job status
     */
    public function getStatus(Job $job) {
        $workDir = $this->getWorkDir($job);
        $lockFileName = $workDir.$job->getJobUid().".lock";
        return intval(!file_exists($lockFileName)); // 0 if the job is running, 1 if it is finished
    }

    /**
     * Try to kill a job. Depending on the scheduling system, this may not be possible
     *
     * @param Genouest\Bundle\SchedulerBundle\Entity\Job A job object
     * @returns bool True if the given job has been killed, false otherwise.
     */
    public function kill(Job $job) {
        return false; // No way to kill a job running locally
    }
    
    /**
     * Is the given job finished?
     *
     * @param Genouest\Bundle\SchedulerBundle\Entity\Job A job object
     * @returns bool True if the given job is finished, false otherwise.
     */
    public function isFinished(Job $job) {
        $status = $this->getStatus($job);
        
        return ($status > 0);
    }
  
    /**
     * Get a string describing the job status retrieved from DRMAA
     *
     * @param int a job status code
     * @returns string Job status as a string
     */
    public function getStatusAsText($status) {
        if (array_key_exists($status, $this->textJobState)) {
        
            return $this->textJobState[$status];
        }
        
        // Default is 'running'
        return $this->textJobState[0];
    }
    
    /**
     * Get the working directory of the job
     *
     * @param Genouest\Bundle\SchedulerBundle\Entity\Job A job object
     * @returns string The work dir of the given job.
     */
    public function getWorkDir(Job $job) {
        $workDir = $this->addTrailingSlash($this->container->getParameter('scheduler.work_dir')).$job->getProgramName().'/'.$job->getJobUid().'/';

        if (!is_dir($workDir))
            mkdir($workDir, 0777, true);

        return $workDir;
    }
    
    /**
     * Add a trailing slash to an URL (only if there isn't already one).
     *
     * @returns string Result Url.
     */
    protected function addTrailingSlash($path) {
        $strEnd = substr($path, -1);
        if ($strEnd != '/')
            $path .= '/';
        
        return $path;
    }

    /**
     * Get the url prefix to access a job results (no hostname)
     *
     * @param Genouest\Bundle\SchedulerBundle\Entity\Job A job object
     * @returns string The results url prefix of the given job.
     */
    public function getResultUrl(Job $job) {
        
        return $this->container->getParameter('scheduler.result_url').$job->getProgramName().'/'.$job->getJobUid().'/';
    }

}
