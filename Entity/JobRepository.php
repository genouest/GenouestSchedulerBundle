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
 
use Doctrine\ORM\EntityRepository;
 
class JobRepository extends EntityRepository
{

    /**
     * Get all jobs for a given user ID.
     *
     * @param $user_id The user ID.
     * @param $days Return the jobs launched during the last xx number of days
     * @returns array An array of Genouest\Bundle\SchedulerBundle\Entity\Job
     */
    public function getJobsForUser($user_id, $days) {
        $date = date('Y-m-d', strtotime("-".intval($days)." day"));
        
        $qb = $this->createQueryBuilder('Job');
        $qb->select('j')
           ->from('Genouest\Bundle\SchedulerBundle\Entity\Job', 'j')
           ->where('j.userId = ?1')
           ->andWhere('j.createdAt > ?2')
           ->orderBy('j.createdAt', 'DESC')
           ->setParameter(1, $user_id)
           ->setParameter(2, $date);

        return $qb->getQuery()->execute();
    }
}
