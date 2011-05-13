<?php

namespace Genouest\Bundle\SchedulerBundle\Entity;
 
use Doctrine\ORM\EntityRepository;
 
class JobRepository extends EntityRepository
{

    /**
     * Get all jobs for a given user ID.
     *
     * @param $user_id The user ID.
     * @returns array An array of JobManagerJob objects as returned by Doctrine persistence layer. // FIXME update
     */
    public function getJobsForUser($user_id) {

        $qb = $this->createQueryBuilder('Job');
        $qb->select('j')
           ->from('Genouest\Bundle\SchedulerBundle\Entity\Job', 'j')
           ->where('j.userId = ?1')
           // FIXME where createdAt < 8j
           // FIXME pagination
           ->orderBy('j.createdAt')
           ->setParameter(1, $user_id);

        return $qb->getQuery()->execute();
    }
}
