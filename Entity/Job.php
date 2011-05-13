<?php

namespace Genouest\Bundle\SchedulerBundle\Entity;

use \Doctrine\Common\Collections\ArrayCollection;
use Genouest\Bundle\SchedulerBundle\Model\Job as BaseJob;

/**
 * @orm:Entity(repositoryClass="Genouest\Bundle\SchedulerBundle\Entity\JobRepository")
 * @orm:HasLifecycleCallbacks
 */
class Job extends BaseJob {

    /**
     * @orm:Id
     * @orm:Column(name="job_uid", type="string", length="255")
     * @orm:GeneratedValue(strategy="NONE")
     */
    protected $jobUid;

    /**
     * @orm:Column(name="scheduler_jobid", type="string", length="255", nullable=TRUE)
     */
	  protected $schedulerJobId;

    /**
     * @orm:Column(name="user_id", type="string", length="255", nullable=TRUE)
     */
	  protected $userId;

    /**
     * @orm:Column(name="command", type="text")
     */
	  protected $command;

    /**
     * @orm:Column(name="program_name", type="string", length="255", nullable=TRUE)
     */
	  protected $programName;

    /**
     * @orm:Column(name="title", type="string", length="255", nullable=TRUE)
     */
	  protected $title;

    /**
     * @orm:Column(name="email", type="string", length="255", nullable=TRUE)
     */
	  protected $email;

    /**
     * @orm:Column(name="back_url", type="string", length="255", nullable=TRUE)
     */
	  protected $backUrl;

    /**
     * @orm:Column(name="result_page", type="string", length="255", nullable=TRUE)
     */
	  protected $resultPage;

    /**
     * @orm:Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @orm:Column(name="updated_at", type="datetime")
     */
    protected $updatedAt;

    /**
     * @orm:OneToMany(targetEntity="ResultFile", mappedBy="job", cascade={"persist", "remove"})
     */
	  protected $resultFiles;

    /**
     * @orm:OneToMany(targetEntity="ResultViewer", mappedBy="job", cascade={"persist", "remove"})
     */
	  protected $resultViewers;
	  

	  public function __construct() {
        $this->resultFiles = new ArrayCollection();
        $this->resultViewers = new ArrayCollection();

       	// constructor is never called by Doctrine
        $this->createdAt = $this->updatedAt = new \DateTime("now");
    }

    /**
     * @orm:PreUpdate
     */
    public function resetUpdated()
    {
        $this->updatedAt = new \DateTime("now");
    }
    
    /**
     * Get jobUid
     *
     * @return string $jobUid
     */
    public function getJobUid()
    {
        return $this->jobUid;
    }

    /**
     * Set schedulerJobId
     *
     * @param string $schedulerJobId
     */
    public function setSchedulerJobId($schedulerJobId)
    {
        $this->schedulerJobId = $schedulerJobId;
    }

    /**
     * Get schedulerJobId
     *
     * @return string $schedulerJobId
     */
    public function getSchedulerJobId()
    {
        return $this->schedulerJobId;
    }

    /**
     * Set userId
     *
     * @param string $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * Get userId
     *
     * @return string $userId
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set command
     *
     * @param text $command
     */
    public function setCommand($command)
    {
        $this->command = $command;
    }

    /**
     * Get command
     *
     * @return text $command
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * Set programName
     *
     * @param string $programName
     */
    public function setProgramName($programName)
    {
        $this->programName = $programName;
    }

    /**
     * Get programName
     *
     * @return string $programName
     */
    public function getProgramName()
    {
        return $this->programName;
    }

    /**
     * Set email
     *
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * Get email
     *
     * @return string $email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set backUrl
     *
     * @param string $backUrl
     */
    public function setBackUrl($backUrl)
    {
        $this->backUrl = $backUrl;
    }

    /**
     * Get backUrl
     *
     * @return string $backUrl
     */
    public function getBackUrl()
    {
        return $this->backUrl;
    }

    /**
     * Set resultPage
     *
     * @param string $resultPage
     */
    public function setResultPage($resultPage)
    {
        $this->resultPage = $resultPage;
    }

    /**
     * Get resultPage
     *
     * @return string $resultPage
     */
    public function getResultPage()
    {
        return $this->resultPage;
    }

    /**
     * Set title
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Get title
     *
     * @return string $title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set createdAt
     *
     * @param datetime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * Get createdAt
     *
     * @return datetime $createdAt
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     *
     * @param datetime $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Get updatedAt
     *
     * @return datetime $updatedAt
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Get resultFiles
     *
     * @return Doctrine\Common\Collections\Collection $resultFiles
     */
    public function getResultFiles()
    {
        return $this->resultFiles;
    }

    /**
     * Get resultViewers
     *
     * @return Doctrine\Common\Collections\Collection $resultViewers
     */
    public function getResultViewers()
    {
        return $this->resultViewers;
    }
    
    /**
     * Add resultFiles
     *
     * @param Genouest\Bundle\SchedulerBundle\Entity\ResultFile $resultFiles
     */
    public function addResultFiles(\Genouest\Bundle\SchedulerBundle\Entity\ResultFile $resultFiles)
    {
        $this->resultFiles[] = $resultFiles;
    }

    /**
     * Add resultViewers
     *
     * @param Genouest\Bundle\SchedulerBundle\Entity\ResultViewer $resultViewers
     */
    public function addResultViewers(\Genouest\Bundle\SchedulerBundle\Entity\ResultViewer $resultViewers)
    {
        $this->resultViewers[] = $resultViewers;
    }
    
    /**
     * Add resultFiles from an array
     *
     * @param $resultFiles array with display name as key and fs path as value
     */
    public function addResultFilesArray($resultFiles)
    {
        foreach ($resultFiles as $resName => $resPath) {
            $resFile = new ResultFile();
            $resFile->setDisplayName($resName);
            $resFile->setFsName($resPath);
            $this->addResultFiles($resFile);
            $resFile->setJob($this);
        }
    }
    
    /**
     * Add resultViewers from an array
     *
     * @param $resultViewers array with display name as key and url as value
     */
    public function addResultViewersArray($resultViewers)
    {
        foreach ($resultViewers as $resName => $resUrl) {
          $resViewer = new ResultViewer();
          $resViewer->setDisplayName($resName);
          $resViewer->setUrl($resUrl);
          $this->addResultViewers($resViewer);
          $resViewer->setJob($this);
        }
    }
}
