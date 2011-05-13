<?php
namespace Genouest\Bundle\SchedulerBundle\Scheduler;

use Genouest\Bundle\SchedulerBundle\Entity\Job;

interface SchedulerInterface {

    /**
     * Launch a job.
     *
     * @param Genouest\Bundle\SchedulerBundle\Entity\Job A job object
     * @returns Genouest\Bundle\SchedulerBundle\Entity\Job A launched Job object
     */
	  public function execute(Job $job);
	  
    /**
     * Get the job status as returned by the underlying scheduling system.
     *
     * @param Genouest\Bundle\SchedulerBundle\Entity\Job A job object
     * @returns int Job status
     */
	  public function getStatus(Job $job);
    
    /**
     * Is the given job finished?
     *
     * @param Genouest\Bundle\SchedulerBundle\Entity\Job A job object
     * @returns bool True if the given job is finished, false otherwise.
     */
    public function isFinished(Job $job);
	
    /**
     * Get a string representing the given status code
     *
     * @param int a job status code
     * @returns string Job status as a string
     */
	  public function getStatusAsText($status);
    
    /**
     * Get the job specific local dir only visible by the machine running the job
     *
     * @param Genouest\Bundle\SchedulerBundle\Entity\Job A job object
     * @returns string The work dir of the given job.
     */
	  public function getWorkDir(Job $job);

    /**
     * Get the job specific dir accessible by all the machines
     *
     * @param Genouest\Bundle\SchedulerBundle\Entity\Job A job object
     * @returns string The result dir of the given job.
     */
    public function getResultDir(Job $job);

    /**
     * Get the url prefix to access a job results (no hostname)
     *
     * @param Genouest\Bundle\SchedulerBundle\Entity\Job A job object
     * @returns string The results url prefix of the given job.
     */
    public function getResultUrl(Job $job);

}
