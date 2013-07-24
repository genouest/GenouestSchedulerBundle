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

use Genouest\Bundle\SchedulerBundle\Model\ResultViewer as BaseResultViewer;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class ResultViewer extends BaseResultViewer {

    /**
     * @ORM\Id
     * @ORM\Column(name="viewer_id", type="integer")
     * @ORM\GeneratedValue
     */
    protected $viewerId;

    /**
     * @ORM\ManyToOne(targetEntity="Job", inversedBy="resultFiles")
     * @ORM\JoinColumn(name="job_uid", referencedColumnName="job_uid")
     */
    protected $job;

    /**
     * @ORM\Column(name="url", type="string", length=255)
     */
    protected $url;

    /**
     * @ORM\Column(name="display_name", type="string", length=255)
     */
    protected $displayName;

    /**
     * Get viewerId
     *
     * @return integer $viewerId
     */
    public function getViewerId()
    {
        return $this->viewerId;
    }

    /**
     * Set url
     *
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Get url
     *
     * @return string $url
     */
    public function getUrl()
    {
        return $this->url;
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
