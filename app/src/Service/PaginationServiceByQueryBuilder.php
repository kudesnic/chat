<?php

namespace App\Service;

use Doctrine\ORM\QueryBuilder;

class PaginationServiceByQueryBuilder extends  PaginationServiceAbstract
{

    /**
     * Builds pagination array
     *
     * @param array $qb
     * @param int|null $page
     * @param int|null $perPage
     * @return array
     */
    public function paginate(QueryBuilder $qb, ?int $page = null,  ?int $perPage = null):array
    {
        $this->setCurrentPage($page);
        if(is_null($perPage) == false){
            $this->perPage = $perPage;
        }
        $this->setTotal($qb);
        $this->setCalculatedParams();
        $qb->setMaxResults($this->perPage)->setFirstResult($this->offset);
        $rows = $qb->getQuery()->execute();

        return $this->buildPagination($rows);
    }

    /**
     * Builds pagination array for nested set nodes
     *
     * @param $node
     * @param QueryBuilder $qb
     * @param int|null $page
     * @param int|null $perPage
     * @param bool $directChildren
     * @return array
     */
    public function paginateNodeChildren(
        $node,
        QueryBuilder $qb,
        ?int $page = null,
        ?int $perPage = null,
        bool $directChildren = false
    ) {
        $qb = $this->repository->extendChildrenQueryBuilder($node, $qb, $directChildren, false);

        $this->setCurrentPage($page);
        if(is_null($perPage) == false){
            $this->perPage = $perPage;
        }
        $this->setTotalChildren($qb);
        $this->setCalculatedParams();

        return $this->buildPagination($qb->getQuery()->execute());
    }

    /**
     * Count total
     *
     * @param $criteria
     * @return void
     */
    protected function setTotal($qb):void
    {
        $selectPart = $qb->getDQLPart('select');
        $orderByPart = $qb->getDQLPart('orderBy');
        $this->total = $qb->select('COUNT(' . $qb->getRootAlias() . ')')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();

        //add back removed parts of query
        $qb->add('select', $selectPart);
        $qb->add('orderBy', $orderByPart);
    }

    /**
     * Count total children for nested set node
     *
     * @param $criteria
     * @return void
     */
    protected function setTotalChildren($qb):void
    {
        $this->total = $qb->select('COUNT(node)')->getQuery()
            ->getSingleScalarResult();
    }

}