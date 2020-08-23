<?php

namespace App\Service;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

/**
 * Builds pagination based on QueryBuilder
 *
 * @author     Andrew Derevinako <andreyy.derevjanko@gmail.com>
 * @version    1.0
 */
class PaginationServiceByQueryBuilder extends  PaginationServiceAbstract
{
    /**
     * @var QueryBuilder
     */
    public $qb;
    /**
     * @var Query
     */
    public $query;

    /**
     * Builds pagination array
     *
     * @param QueryBuilder $qb
     * @param int|null $page
     * @param int|null $perPage
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function paginate(QueryBuilder $qb, ?int $page = null,  ?int $perPage = null): array
    {
        if(is_null($this->query)){
            $this->buildQuery($qb, $page, $perPage);
        }
        $rows = $this->query->execute();

        return $this->buildPagination($rows);
    }

    /**
     * @return array
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function paginateFromQuery()
    {
        $rows = $this->query->execute();

        return $this->buildPagination($rows);
    }

    /**
     * @param QueryBuilder $qb
     * @param int|null $page
     * @param int|null $perPage
     * @return PaginationServiceByQueryBuilder
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function buildQuery(QueryBuilder $qb, ?int $page = null,  ?int $perPage = null): self
    {
        $this->setCurrentPage($page);
        if(is_null($perPage) == false){
            $this->perPage = $perPage;
        }
        $this->setTotal($qb);
        $this->setCalculatedParams();
        $this->qb->setMaxResults($this->perPage)->setFirstResult($this->offset);
        $this->query = $this->qb->getQuery();

        return $this;
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
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
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
     * Counts total
     *
     * @param QueryBuilder $qb
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function setTotal(QueryBuilder $qb):void
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
     * @param QueryBuilder $qb
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function setTotalChildren(QueryBuilder $qb):void
    {
        $this->total = $qb->select('COUNT(node)')->getQuery()
            ->getSingleScalarResult();
    }

}