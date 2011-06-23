<?php

namespace Genouest\Bundle\SchedulerBundle\Entity;

use Genouest\Bundle\SchedulerBundle\Model\ResultFile as BaseResultFile;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class ResultFile extends BaseResultFile {

    /**
     * @ORM\Id
     * @ORM\Column(name="file_id", type="integer")
     * @ORM\GeneratedValue
     */
    protected $fileId;

    /**
     * @ORM\ManyToOne(targetEntity="Job", inversedBy="resultFiles")
     * @ORM\JoinColumn(name="job_uid", referencedColumnName="job_uid")
     */
    protected $job;

    /**
     * @ORM\Column(name="display_name", type="string", length="255")
     */
    protected $displayName;
    
    /**
     * @ORM\Column(name="fs_name", type="string", length="255")
     */
    protected $fsName;

    /**
     * Get fileId
     *
     * @return integer $fileId
     */
    public function getFileId()
    {
        return $this->fileId;
    }

    /**
     * Set displayName
     *
     * @param string $displayName
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;
    }

    /**
     * Get displayName
     *
     * @return string $displayName
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * Set fsName
     *
     * @param string $fsName
     */
    public function setFsName($fsName)
    {
        $this->fsName = $fsName;
    }

    /**
     * Get fsName
     *
     * @return string $fsName
     */
    public function getFsName()
    {
        return $this->fsName;
    }
    /**
     * Set job
     *
     * @param Genouest\Bundle\SchedulerBundle\Entity\Job $job
     */
    public function setJob(\Genouest\Bundle\SchedulerBundle\Entity\Job $job)
    {
        $this->job = $job;
    }

    /**
     * Get job
     *
     * @return Genouest\Bundle\SchedulerBundle\Entity\Job $job
     */
    public function getJob()
    {
        return $this->job;
    }
}
