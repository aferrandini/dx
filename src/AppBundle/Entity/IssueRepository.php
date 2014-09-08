<?php

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * IssueRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class IssueRepository extends EntityRepository
{
    public function findNewIssues($filter)
    {
        return $this->findByStatusAndTerm(Issue::STATUS_NEW, $filter, array('createdAt' => 'DESC'));
    }

    public function findDiscussingIssues($filter)
    {
        return $this->findByStatusAndTerm(Issue::STATUS_DISCUSSING, $filter, array('createdAt' => 'DESC'));
    }

    public function findWipIssues($filter)
    {
        return $this->findByStatusAndTerm(Issue::STATUS_WIP, $filter, array('createdAt' => 'DESC'));
    }

    public function findFinishedIssues($filter)
    {
        return $this->findByStatusAndTerm(Issue::STATUS_FINISHED, $filter, array('createdAt' => 'DESC'));
    }

    /**
     * Find issues by status and filter term.
     *
     * @param string $status
     * @param string $term
     * @param array  $orderBy
     *
     * @return array
     */
    public function findByStatusAndTerm($status, $term = '', $orderBy = array())
    {
        $query = $this->createQueryBuilder('i');

        $query
            ->where($query->expr()->eq('i.status', ':status'))
            ->setParameter('status', $status)
        ;

        if (!empty($term)) {
            $query
                ->andWhere($query->expr()->orX(
                    $query->expr()->like('i.title', ':term'),
                    $query->expr()->like('i.repository', ':term')
                ))
                ->setParameter('term', strtr($term, array('*' => '%')))
            ;
        }

        foreach ($orderBy as $field => $direction) {
            $query->addOrderBy('i.' . $field, $direction);
        }

        return $query->getQuery()->getResult();
    }
}
