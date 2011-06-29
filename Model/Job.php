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
 
namespace Genouest\Bundle\SchedulerBundle\Model;

abstract class Job {

    protected $mailSubject;
    protected $mailBody;

    /**
     * Generate a job unique ID (jobUid). Nothing to do with job ID (which is specific to the scheduler).
     *
     * @returns string This job unique ID
     */
    public function generateJobUid() {
        $prefix = $this->getProgramName()."-".rand();
        $this->jobUid = uniqid ($prefix, true);

        // Clean the jobUid (no spaces, dots, strange chars). This allow passing jobUid in parameter
        $this->jobUid = strtolower($this->jobUid);
        // strip all non word chars
        $this->jobUid = preg_replace('/\W/', ' ', $this->jobUid);
        // replace all white space sections with a dash
        $this->jobUid = preg_replace('/\ +/', '-', $this->jobUid);
        // trim dashes
        $this->jobUid = preg_replace('/\-$/', '', $this->jobUid);
        $this->jobUid = preg_replace('/^\-/', '', $this->jobUid);

        return $this->jobUid;
    }
    
    /**
     * Can this job be launched?
     *
     * @returns bool True if this job can be launched, false otherwise.
     */
    public function canBeLaunched() {
        return (empty($this->jobUid) || empty($this->schedulerJobId));
    }
    
    /**
     * Has this job been already launched?
     *
     * @returns bool True if the job has already been launched, false otherwise.
     */
    public function isLaunched() {
        return ($this->isValid() && !empty($this->command));
    }
    
    /**
     * Is this job a valid one?
     *
     * @returns bool True if the job is valid, false otherwise.
     */
    public function isValid() {
        return (!empty($this->jobUid) && !empty($this->schedulerJobId));
    }
    
    /**
     * Is there a user ID?
     *
     * @returns bool True if there is a user ID, false otherwise.
     */
    public function hasUserId() {
        return !empty($this->userId);
    }

    /**
     * Are there some result viewers?
     *
     * @returns bool True if there are some result viewers, false otherwise.
     */
    public function hasResultViewers() {
        return count($this->resultViewers) > 0;
    }

    /**
     * Are there some result files?
     *
     * @returns bool True if there are some result files, false otherwise.
     */
    public function hasResultFiles() {
        return count($this->resultFiles) > 0;
    }
    
    /**
     * Check given mail validity.
     *
     * @param $to Email address to check
     * @returns bool True if email is valid. False otherwise.
     */
    public function checkMail($to) {
        if (preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-])+\.[a-zA-Z]+$/",$to))
            return true;
        else
            return false;
    }
    
    /**
     * Is there a valid email?
     *
     * @returns bool True if there is a valid email, false otherwise.
     */
    public function hasValidEmail() {
        return (!empty($this->email) && $this->checkMail($this->email));
    }

    /**
     * Set email content to be sent.
     *
     * @param $subject Email subject
     * @param $content Email contents
     * @returns bool True if email is valid. False otherwise.
     */
    public function setEmailContent($subject, $content) {
        if ($this->hasValidEmail()) {
            $this->mailSubject = $subject;
            $this->mailBody = $content;

            return true;
        }
        else
            return false;
    }
    
    /**
     * Get the email subject
     *
     * @returns string The subject of the email that will be sent upon job completion
     */
    public function getMailSubject() {
        return $this->mailSubject;
    }
    
    /**
     * Get the email body
     *
     * @returns string The body of the email that will be sent upon job completion
     */
    public function getMailBody() {
        return $this->mailBody;
    }
}
