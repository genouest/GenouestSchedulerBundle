<?php
namespace Genouest\Bundle\SchedulerBundle\Scheduler;

use Symfony\Component\HttpFoundation\File\Exception\FileException;

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

    protected $sharedDir;
    protected $workDir;
    protected $resultUrl;
    protected $mailBin;
    protected $mailAuthor;
	  
	  public function __construct($sharedDir, $workDir, $resultUrl, $mailBin, $mailAuthorName, $mailAuthorAddress) {
	      $this->sharedDir = $this->addTrailingSlash($sharedDir);
	      $this->workDir = $this->addTrailingSlash($workDir);
	      $this->resultUrl = $resultUrl;
	      $this->mailBin = $mailBin;
	      $this->mailAuthorName = $mailAuthorName;
	      $this->mailAuthorAddress = $mailAuthorAddress;
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
        
        // program = generateUid().".sh"
        // write command in program file, add email sending if required
        // Run the script and return jobUid
        $resultDir = $this->getResultDir($job);
        $script = "#!/bin/bash\n";
        $script .= "exec 3>".$resultDir."stdout.txt\n";
        $script .= "exec 4>".$resultDir."stderr.txt\n";
        $script .= "(\n";
        $script .= $this->checkDirectoryScript($job);
        $script .= $job->getCommand()."\n";
        $script .= ") 1>&3 2>&4\n";
        if ($job->hasValidEmail()) {
            $fromField = '';
            if (!empty($this->mailAuthorName) || !empty($this->mailAuthorAddress))
                $fromField = ' -- ';
            if (!empty($this->mailAuthorName))
                $fromField .= ' -F \''.$this->mailAuthorName.'\'';
            if (!empty($this->mailAuthorAddress))
                $fromField .= ' -f \''.$this->mailAuthorAddress.'\'';
            $script.="echo -e '".str_replace("\n","\\n",str_replace("'", "_", $job->getMailBody()))."' | ".$this->mailBin." -s '".str_replace("'", "_", $job->getMailSubject())."' ".$job->getEmail().$fromField."\n";
        }
        $script .= "\n";
        
        $jobFileName = $resultDir.$job->getJobUid().".sh";
        $jobFile = fopen($jobFileName, 'w');
        $fError = $jobFile === false;
        if ($jobFile) {
            $fError = $fError || (false === @fwrite($jobFile,$script));
            $fError = $fError || (false === @fclose($jobFile));
            $fError = $fError || (false === @chmod($jobFileName, 0755));
        }

        if ($fError) {
            $error = error_get_last();
            throw new FileException(sprintf('Could not create file %s (%s)', $resultDir.$job->getJobUid().".sh", strip_tags($error['message'])));
        }
        
        $jobId = qsub($jobFileName, $job->getProgramName()); // No need to check if NULL as if it is, there's a PHP error catched by Symfony
        $job->setSchedulerJobId($jobId);
        
        return $job;
	  }

    /**
     * Get the job status
     *
     * @param Genouest\Bundle\SchedulerBundle\Entity\Job A job object
     * @returns int Job status
     */
	  public function getStatus(Job $job) {
		    return qstat($job->getSchedulerJobId());
	  }
    
    /**
     * Is the given job finished?
     *
     * @param Genouest\Bundle\SchedulerBundle\Entity\Job A job object
     * @returns bool True if the given job is finished, false otherwise.
     */
    public function isFinished(Job $job) {
        $status = $this->getStatus($job);
        
        return (in_array($status, array(self::STATE_UNDETERMINED, self::STATE_FINISHED, self::STATE_FAILED)));
    }
	
    /**
     * Get a string describing the job status retrieved from DRMAA
     *
     * @param int a job status code
     * @returns string Job status as a string
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
     * Get the local dir only visible by the machine running the job
     *
     * @param Genouest\Bundle\SchedulerBundle\Entity\Job A job object
     * @returns string The work dir of the given job.
     */
	  public function getWorkDir(Job $job) {
	      $workDir = $this->workDir.$job->getProgramName().'/'.$job->getJobUid().'/';

	      if (!is_dir($workDir))
      	    mkdir($workDir, 0777, true);

		    return $workDir;
	  }

    /**
     * Get the dir accessible by all the machines
     *
     * @param Genouest\Bundle\SchedulerBundle\Entity\Job A job object
     * @returns string The result dir of the given job.
     */
    public function getResultDir(Job $job) {
        $resultDir = $this->sharedDir.$job->getProgramName().'/'.$job->getJobUid().'/';

        if (!is_dir($resultDir))
            mkdir($resultDir, 0777, true);

        return $resultDir;
    }
	  

    /**
     * Generate shell script to check directory existence.
     *
     * @param Genouest\Bundle\SchedulerBundle\Entity\Job A job object
     * @returns string Shell script to check directory existence
     */
	  protected function checkDirectoryScript(Job $job) {
        $script = "if [ ! -d ".$this->getWorkDir($job)." ]\n";
        $script .= "then\n";
        $script .= "mkdir -p ".$this->getWorkDir($job)."\n";
        $script .= "fi\n";
        $script .= "if [ ! -d ".$this->getResultDir($job)." ]\n";
        $script .= "then\n";
        $script .= "mkdir -p ".$this->getResultDir($job)."\n";
        $script .= "fi\n";
        return $script;
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
	      
	      return $this->resultUrl.$job->getProgramName().'/'.$job->getJobUid().'/';
    }

}
