<?php

namespace Genouest\Bundle\SchedulerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Genouest\Bundle\SchedulerBundle\Entity\Job;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;


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
    
        $scheduler = $this->get('scheduler.method');
        $jobRepo = $this->get('job.repository');
        $em = $this->get('scheduler.object_manager');
        
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
            
            $body = $this->render('SchedulerBundle:Scheduler:email.html.twig', array('job' => $job, 'resultUrlPrefix' => $resultUrlPrefix))->getContent();
            
            $job->setEmailContent($subject, $body);
        }
        
        $job->setResultPage($resultUrlPrefix);
        
        
        try {
            $job = $scheduler->execute($job);
        }
        catch (InvalidJobException $e) {
            // Save the job
            $em->persist($job);
            $em->flush();
            
            return $this->render('SchedulerBundle:Scheduler:error.html.twig', array('job' => $job, 'error' => $e->getMessage()));
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
        $scheduler = $this->get('scheduler.method');
        $jobRepo = $this->get('job.repository');
        $job = $jobRepo->find($uid);
        
        // Check that job is valid
        if (!$job || !$job->isLaunched())
            return $this->render('SchedulerBundle:Scheduler:error.html.twig', array('job' => $job, 'uid' => $uid, 'error' => 'Job '.$uid.' is not available.'));
        
        
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
        
        return $this->render('SchedulerBundle:Scheduler:status.html.twig', array('job' => $job, 'status' => $textStatus));
    }

    /**
     * Show results
     *
     * @Route("/job/results/{uid}", name = "_job_results")
     * @Template()
     */
    public function jobResultsAction($uid) {
        // Load job from db
        $scheduler = $this->get('scheduler.method');
        $jobRepo = $this->get('job.repository');
        $job = $jobRepo->find($uid);
        
        // Check that job is valid
        if (!$job || !$job->isLaunched())
            return $this->render('SchedulerBundle:Scheduler:error.html.twig', array('job' => $job, 'uid' => $uid, 'error' => 'Job '.$uid.' is not available.'));
        
        // Finished?
        if (!$scheduler->isFinished($job)) {
            return new RedirectResponse($this->generateUrl('_job_status', array('uid' => $job->getJobUid())));
        }
        
        $textStatus = $scheduler->getStatusAsText($scheduler->getStatus($job));
        $resultUrl = $scheduler->getResultUrl($job);
        
        return $this->render('SchedulerBundle:Scheduler:results.html.twig', array('job' => $job, 'status' => $textStatus, 'resultUrl' => $resultUrl));
    }
    
    /**
     * Show the job history of current user
     *
     * @Route("/job/history", name = "_job_history")
     * @Template()
     */
    public function historyAction() {
        $jobRepo = $this->get('job.repository');
        $jobs = $jobRepo->getJobsForUser("");
        
        return $this->render('SchedulerBundle:Scheduler:history.html.twig', array('jobs' => $jobs));
    }
}
