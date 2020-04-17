<?php

namespace App\Service;

use Doctrine\Common\Collections\Criteria;

/**
 * Builds pagination based on Criteria
 *
 * @author     Andrew Derevinako <andreyy.derevjanko@gmail.com>
 * @version    1.0
 */
class PaginationServiceByCriteria extends  PaginationServiceAbstract
{

    /**
     * Builds pagination array
     *
     * @param Criteria $criteria
     * @param int|null $page
     * @param int|null $perPage
     * @return array
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function paginate(Criteria $criteria, ?int $page = null,  ?int $perPage = null):array
    {
        $this->setCurrentPage($page);
        if(is_null($perPage) == false){
            $this->perPage = $perPage;
        }
        $this->setTotal($criteria);
        $this->setCalculatedParams();
        $this->addCriteriaLimit($criteria);
        $rows = $this->repository
            ->matching($criteria)
            ->toArray();

        return $this->buildPagination($rows);
    }

    /**
     * Builds pagination array for nested set nodes
     *
     * @param $node
     * @param Criteria $criteria
     * @param int|null $page
     * @param int|null $perPage
     * @param bool $directChildren
     * @return array
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function paginateNodeChildren(
        $node,
        Criteria $criteria,
        ?int $page = null,
        ?int $perPage = null,
        bool $directChildren = false
    ) {
        $criteria->setMaxResults($this->perPage)
            ->setFirstResult($this->offset);
        $this->setCurrentPage($page);
        if(is_null($perPage) == false){
            $this->perPage = $perPage;
        }
        $this->setTotalChildren($node, $directChildren);
        $this->setCalculatedParams([]);
        $this->addCriteriaLimit($criteria);
        $rows = $this->repository
            ->findChildrenByCriteria($node, $criteria, $directChildren)
            ->toArray();

        return $this->buildPagination($rows);
    }

    /**
     * Counts total
     *
     * @param $criteria
     * @return void
     */
    protected function setTotal($criteria):void
    {
        $this->total = $this->repository->matching($criteria)->count();
    }

    /**
     * Counts total children for nested set node
     *
     * @param $node
     * @param bool $directChildren
     * @return void
     */
    protected function setTotalChildren($node, bool $directChildren = false):void
    {
        $this->total = $this->repository->countChildren($node, $directChildren);
    }

    /**
     * @param Criteria $criteria
     * @return Criteria
     */
    private function addCriteriaLimit(Criteria $criteria):Criteria
    {
        return $criteria->setMaxResults($this->perPage)->setFirstResult($this->offset);
    }

}