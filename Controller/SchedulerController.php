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

namespace Genouest\Bundle\SchedulerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

use Genouest\Bundle\SchedulerBundle\Exception\InvalidJobException;
use Genouest\Bundle\SchedulerBundle\Entity\Job;

class SchedulerController extends Controller
{
    /**
     * Execute the job given by the application
     *
     * No route associated
     *
     * @param Genouest\Bundle\SchedulerBundle\Model\Job A job to launch
     */
    public function launchJobAction(Job $job) {
    
        $scheduler = $this->get('scheduler.scheduler');
        $jobRepo = $this->get('job.repository');
        $em = $this->get('scheduler.entity_manager');
        
        $resultUrlPrefix = $scheduler->getResultUrl($job);
        if (false === strpos($resultUrlPrefix, '://')) // If no host specified, assume it's the same
            $resultUrlPrefix = $this->get('request')->getScheme().'://'.$this->get('request')->getHttpHost().$resultUrlPrefix;
        
        // Prepare the mail to be sent when job finished (added to job command line)
        if ($job->hasValidEmail()) {
            // Fill the mail body
            $title = $job->getTitle();
            if (!empty($title)) {
                $title = "'".$title."' (".$job->getJobUid().")";
            }
            else {
                $title = $job->getJobUid();
            }
            $subject =  $job->getProgramName(). ": completion of job ".$title;
            
            $body = $this->render('GenouestSchedulerBundle:Scheduler:email.html.twig', array('job' => $job, 'resultUrlPrefix' => $resultUrlPrefix))->getContent();
            
            $job->setEmailContent($subject, $body);
        }
        
        $job->setResultPage($resultUrlPrefix);
        
        // Assign the job to an user if we are logged in
        if ($this->get('security.context')->getToken() && $this->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY'))
            $job->setUserId($this->get('security.context')->getToken()->getUsername());
        
        // Launch the job
        try {
            $job = $scheduler->execute($job);
        }
        catch (InvalidJobException $e) {
            // Save the job
            $em->persist($job);
            $em->flush();
            
            return $this->render('GenouestSchedulerBundle:Scheduler:error.html.twig', array('job' => $job, 'uid' => $job->getJobUid(), 'error' => $e->getMessage()));
        }
        
        // Save the job
        $em->persist($job);
        $em->flush();

        // No error, track status of our job
        return new RedirectResponse($this->generateUrl('_job_status', array('uid' => $job->getJobUid(), '_format' => 'html')));
    }

    /**
     *  Get the status of a job and display the results if finished (or wait until it is finished).
     *
     * @Route("/job/status/{uid}.{_format}", name = "_job_status", requirements = {"_format" = "html|js"})
     * @Template()
     *
     * @param $uid string The uid of the job to watch
     */
    public function jobStatusAction($uid) {
    
        // Load job from db
        $scheduler = $this->get('scheduler.scheduler');
        $jobRepo = $this->get('job.repository');
        $job = $jobRepo->find($uid);
        
        // Check that job is valid
        if (!$job || !$job->isLaunched())
            return $this->render('GenouestSchedulerBundle:Scheduler:error.html.twig', array('job' => $job, 'uid' => $uid, 'error' => 'Job '.$uid.' is not available.'));
        
        
        $textStatus = $scheduler->getStatusAsText($scheduler->getStatus($job));
        $isFinished = $scheduler->isFinished($job);
        $resultUrl = $this->generateUrl('_job_results', array('uid' => $job->getJobUid()));
        
        if ($this->get('request')->getRequestFormat() == 'js') {
        
            // Ajax mode
            $resData = array();
            $resData['status'] = $textStatus;
            $resData['shouldRedirect'] = $isFinished;
            $resData['resultsUrl'] = $resultUrl;
            
            $response = new Response(json_encode($resData));
            $response->headers->set('Content-Type', 'application/json; charset=utf-8');
            
            // Ensure the browser doesn't keep json content in cache
            $date = new \DateTime();
            $date->modify('-6000 seconds');
            $response->setExpires($date);
            $response->headers->addCacheControlDirective('must-revalidate', true);
            
            return $response;
        }
        else if ($isFinished) {
            // Job finished and not in ajax mode: simply redirect
            return new RedirectResponse($resultUrl);
        }
        
        return $this->render('GenouestSchedulerBundle:Scheduler:status.html.twig', array('job' => $job, 'status' => $textStatus));
    }

    /**
     *  Kill a job.
     *
     * @Route("/job/kill/{uid}", name = "_job_kill")
     * @Template()
     *
     * @param $uid string The uid of the job to kill
     */
    public function jobKillAction($uid) {
    
        // Load job from db
        $scheduler = $this->get('scheduler.scheduler');
        $jobRepo = $this->get('job.repository');
        $job = $jobRepo->find($uid);
        
        // Check that job is valid
        if (!$job || !$job->isLaunched())
            return $this->render('GenouestSchedulerBundle:Scheduler:error.html.twig', array('job' => $job, 'uid' => $uid, 'error' => 'Job '.$uid.' is not available.'));
        
        $isFinished = $scheduler->isFinished($job);
        
        if ($isFinished) {
            // Job finished (already killed, or finished before accessing this page): simply redirect to result page
            $resultUrl = $this->generateUrl('_job_results', array('uid' => $job->getJobUid()));
            return new RedirectResponse($resultUrl);
        }
        
        $success = $scheduler->kill($job);
        
        return $this->render('GenouestSchedulerBundle:Scheduler:kill.html.twig', array('job' => $job, 'success' => $success));
    }

    /**
     * Show results
     *
     * @Route("/job/results/{uid}", name = "_job_results")
     * @Template()
     */
    public function jobResultsAction($uid) {
        // Load job from db
        $scheduler = $this->get('scheduler.scheduler');
        $jobRepo = $this->get('job.repository');
        $job = $jobRepo->find($uid);
        
        // Check that job is valid
        if (!$job || !$job->isLaunched())
            return $this->render('GenouestSchedulerBundle:Scheduler:error.html.twig', array('job' => $job, 'uid' => $uid, 'error' => 'Job '.$uid.' is not available.'));
        
        // Finished?
        if (!$scheduler->isFinished($job)) {
            return new RedirectResponse($this->generateUrl('_job_status', array('uid' => $job->getJobUid())));
        }
        
        $textStatus = $scheduler->getStatusAsText($scheduler->getStatus($job));
        $resultUrl = $scheduler->getResultUrl($job);
        
        return $this->render('GenouestSchedulerBundle:Scheduler:results.html.twig', array('job' => $job, 'status' => $textStatus, 'resultUrl' => $resultUrl));
    }
    
    /**
     * Show the job history of current user
     *
     * @Route("/job/history", name = "_job_history")
     * @Template()
     */
    public function historyAction() {
        $jobRepo = $this->get('job.repository');
        $scheduler = $this->get('scheduler.scheduler');
        
        $jobs = array();
        
        // Try to find the jobs of current user, if he is authenticated and not anonymous
        if ($this->get('security.context')->getToken() && $this->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY'))
            $jobs = $jobRepo->getJobsForUser($this->get('security.context')->getToken()->getUsername(), $this->container->getParameter('scheduler.history_length'));
        
        return $this->render('GenouestSchedulerBundle:Scheduler:history.html.twig', array('jobs' => $jobs, 'scheduler' => $scheduler));
    }
}
