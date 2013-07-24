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
     * @ORM\Column(name="display_name", type="string", length=255)
     */
    protected $displayName;
    
    /**
     * @ORM\Column(name="fs_name", type="string", length=255)
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
