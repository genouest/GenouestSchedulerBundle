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

class DrmaaScheduler implements SchedulerInterface {
    
    // Drmaa job state codes
    const STATE_UNDETERMINED                  =  0; /* process status cannot be determined */
    const STATE_WAITING                       = 16; /* job is queued and active */
    const STATE_WAITING_SYSTEM_ON_HOLD        = 17; /* job is queued and in system hold */
    const STATE_WAITING_USER_ON_HOLD          = 18; /* job is queued and in user hold */
    const STATE_WAITING_USER_SYSTEM_ON_HOLD   = 19; /* job is queued and in user and system hold */
    const STATE_RUNNING                       = 32; /* job is running */
    const STATE_SYSTEM_SUSPENDED              = 33; /* job is system suspended */
    const STATE_USER_SUSPENDED                = 34; /* job is user suspended */
    const STATE_USER_SYSTEM_SUSPENDED         = 35; /* job is user and system suspended */
    const STATE_FINISHED                      = 48; /* job finished normally */
    const STATE_FAILED                        = 64; /* job finished, but failed */
    
    protected $textJobState = array(
        self::STATE_UNDETERMINED => 'unknown',
        self::STATE_WAITING => 'waiting',
        self::STATE_WAITING_SYSTEM_ON_HOLD => 'waiting',
        self::STATE_WAITING_USER_ON_HOLD => 'waiting',
        self::STATE_WAITING_SYSTEM_ON_HOLD => 'waiting',
        self::STATE_RUNNING => 'running',
        self::STATE_SYSTEM_SUSPENDED => 'suspended',
        self::STATE_USER_SUSPENDED => 'suspended',
        self::STATE_USER_SYSTEM_SUSPENDED => 'suspended',
        self::STATE_FINISHED => 'finished',
        self::STATE_FAILED => 'failed',
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
     * @return Genouest\Bundle\SchedulerBundle\Entity\Job A launched Job object
     */
    public function execute(Job $job) {
        
        if (!$job->canBeLaunched()) {
            // Cannot launch the job
            throw new InvalidJobException('Server side problem: the job cannot be launched.');
        }
        
        // program = generateUid().".sh"
        // write command in program file, add email sending if required
        // Run the script and return jobUid
        $workDir = $this->getWorkDir($job);
        
        $script = $this->container->get('templating')->render('GenouestSchedulerBundle:Scheduler:script_drmaa.sh.twig', array('job' => $job,
            'workDir' => $workDir,
            'tempDir' => $this->getTempDir($job),
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
        
        if ($this->container->hasParameter('scheduler.drmaa_native'))
            $jobId = qsub($jobFileName, $job->getProgramName(), $this->container->getParameter('scheduler.drmaa_native'));
        else
            $jobId = qsub($jobFileName, $job->getProgramName());
        
        // No need to check if jobid is NULL as if it is, there's a PHP error catched by Symfony
        $job->setSchedulerJobId($jobId); // This must to be set when the job is launched.
        
        return $job;
    }

    /**
     * Get the job status
     *
     * @param Genouest\Bundle\SchedulerBundle\Entity\Job A job object
     * @return int Job status
     */
    public function getStatus(Job $job) {
        return qstat($job->getSchedulerJobId());
    }

    /**
     * Try to kill a job. Depending on the scheduling system, this may not be possible
     *
     * @param Genouest\Bundle\SchedulerBundle\Entity\Job A job object
     * @return bool True if the given job has been killed, false otherwise.
     */
    public function kill(Job $job) {
        return qdel($job->getSchedulerJobId());
    }
    
    /**
     * Is the given job finished?
     *
     * @param Genouest\Bundle\SchedulerBundle\Entity\Job A job object
     * @return bool True if the given job is finished, false otherwise.
     */
    public function isFinished(Job $job) {
        $status = $this->getStatus($job);
        
        return (in_array($status, array(self::STATE_UNDETERMINED, self::STATE_FINISHED, self::STATE_FAILED)));
    }
  
    /**
     * Get a string describing the job status retrieved from DRMAA
     *
     * @param int a job status code
     * @return string Job status as a string
     */
    public function getStatusAsText($status) {
        if (array_key_exists($status, $this->textJobState)) {
        
            if ($status === self::STATE_UNDETERMINED)
                return $this->textJobState[self::STATE_FINISHED]; // Undetermined means finished for drmaa
            
            return $this->textJobState[$status];
        }
        
        // Default is 'unknown'
        return $this->textJobState[0];
    }
    
    /**
     * Get the working directory of the job
     *
     * @param Genouest\Bundle\SchedulerBundle\Entity\Job A job object
     * @return string The work dir of the given job.
     */
    public function getWorkDir(Job $job) {
        $workDir = $this->addTrailingSlash($this->container->getParameter('scheduler.work_dir')).$job->getProgramName().'/'.$job->getJobUid().'/';

        if (!is_dir($workDir))
            mkdir($workDir, 0777, true);

        return $workDir;
    }

    /**
     * Get the dir accessible by all the machines
     *
     * @param Genouest\Bundle\SchedulerBundle\Entity\Job A job object
     * @return string The temp dir of the given job.
     */
    public function getTempDir(Job $job) {
        $tempDir = $this->addTrailingSlash($this->container->getParameter('scheduler.drmaa_temp_dir')).$job->getProgramName().'/'.$job->getJobUid().'/';

        if (!is_dir($tempDir))
            mkdir($tempDir, 0777, true);

        return $tempDir;
    }
    
    /**
     * Add a trailing slash to an URL (only if there isn't already one).
     *
     * @return string Result Url.
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
     * @return string The results url prefix of the given job.
     */
    public function getResultUrl(Job $job) {
        
        return $this->container->getParameter('scheduler.result_url').$job->getProgramName().'/'.$job->getJobUid().'/';
    }

}
