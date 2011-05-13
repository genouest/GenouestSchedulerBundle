<?php
namespace Genouest\Bundle\SchedulerBundle\Scheduler;

class SchedulerFactory {
    
    /**
     * Get a scheduler with given id and given parameters
     *
     * @param string $schedulerId The id of the asked scheduler
     * @param string $sharedDir
     * @param string $workDir
     * @param string $resultUrl
     * @param string $mailBin
     * @param string $mailAuthor 
     */
    public static function getScheduler($schedulerId, $sharedDir, $workDir, $resultUrl, $mailBin, $mailAuthorName, $mailAuthorAddress) {
    
        if ($schedulerId == "drmaa") {
            return new DrmaaScheduler($sharedDir, $workDir, $resultUrl, $mailBin, $mailAuthorName, $mailAuthorAddress);
        }
        
        return null;
        
    }
}
