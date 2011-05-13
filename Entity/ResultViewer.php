<?php

namespace Genouest\Bundle\SchedulerBundle\Entity;

use Genouest\Bundle\SchedulerBundle\Model\ResultViewer as BaseResultViewer;

/**
 * @orm:Entity
 */
class ResultViewer extends BaseResultViewer {

    /**
     * @orm:Id
     * @orm:Column(name="viewer_id", type="integer")
     * @orm:GeneratedValue
     */
    protected $viewerId;

    /**
     * @orm:ManyToOne(targetEntity="Job", inversedBy="resultFiles")
     * @orm:JoinColumn(name="job_uid", referencedColumnName="job_uid")
     */
    protected $job;

    /**
     * @orm:Column(name="url", type="string", length="255")
     */
    protected $url;

    /**
     * @orm:Column(name="display_name", type="string", length="255")
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