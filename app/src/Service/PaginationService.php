<?php

namespace App\Service;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

class PaginationService
{
    private $em;
    private $objectNormalizer;
    private $repository;
    private $total;
    private $currentPage;
    private $pagesCount;
    private $perPage;
    private $offset;
    private $normalize = false;

    public function __construct(EntityManagerInterface $em, ContainerBagInterface $params, ObjectNormalizer $objectNormalizer)
    {
        $this->em = $em;
        $this->objectNormalizer = $objectNormalizer;
        $this->perPage = $params->get('pagination.per_page');
        if($params->has('pagination.normalize')){
            $this->normalize = $params->get('pagination.normalize');

        }
    }

    /**
     * Sets the repository for a class.
     *
     * @param string $className
     *
     * @return self
     */
    public function setRepository(string $className)
    {
        $this->repository = $this->em->getRepository($className);

        return $this;
    }

    /**
     * gets the repository for a class.
     *
     * @param string $className
     *
     * @return self
     */
    public function getRepository():ObjectRepository
    {
        return $this->repository;
    }

    /**
     * Builds pagination array
     *
     * @param array $criteria
     * @param int|null $page
     * @param int|null $perPage
     * @return array
     */
    public function paginate(Criteria $criteria, ?int $page = null,  ?int $perPage = null)
    {
        $this->setCurrentPage($page);
        if(is_null($perPage) == false){
            $this->perPage = $perPage;
        }
        $this->setTotal($criteria);
        $this->setCalculatedParams();
        $this->addCriteriaLimit($criteria);
        $rows = $this->repository->matching($criteria)->toArray();

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
     */
    public function paginateNodeChildren($node, Criteria $criteria, ?int $page = null,  ?int $perPage = null, bool $directChildren = false)
    {
        $criteria->setMaxResults($this->perPage)->setFirstResult($this->offset);
        $this->setCurrentPage($page);
        if(is_null($perPage) == false){
            $this->perPage = $perPage;
        }
        $this->setTotalChildren($node, $directChildren);
        $this->setCalculatedParams([]);
        $this->addCriteriaLimit($criteria);
        $rows = $this->repository->findChildrenBy($node, $criteria, $directChildren)->toArray();

        return $this->buildPagination($rows);
    }

    /**
     * Sets current page
     *
     * @param int|null $page
     */
    private function setCurrentPage(?int $page)
    {
        if(is_null($page)){
            $this->currentPage = 1;
        } else {
            $this->currentPage = $page;
        }
    }

    /**
     * Count total
     *
     * @param $criteria
     */
    private function setTotal($criteria)
    {
        $this->total = $this->repository->matching($criteria)->count();
    }

     /**
     * Count total children for nested set node
     *
     * @param $criteria
     */
    private function setTotalChildren($node, bool $directChildren = false)
    {
        $this->total = $this->repository->countChildren($node, $directChildren);
    }

    /**
     * Sets such calculated params, such as total and pagesCount
     *
     */
    private function setCalculatedParams()
    {
        //round up
        $this->pagesCount = ceil($this->total / $this->perPage);

        if($this->currentPage == 1){
            $this->offset = 0;
        } else {
            $this->offset = ($this->currentPage - 1) * $this->perPage;
        }

    }

    /**
     * @param array|null $rows
     * @return array
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    private function buildPagination(?array $rows)
    {
        if($this->normalize){
            $classMetadataFactory = null;
            $nameConverter = new CamelCaseToSnakeCaseNameConverter();

            $normalizer = new PropertyNormalizer($classMetadataFactory, $nameConverter);
            $serializer = new Serializer([$normalizer]);

            $data = [];
            foreach ($rows as $row){
                $data[] = $serializer->normalize($row);
            }
        } else {
            $data = $rows;
        }

        return [
            'currentPage' => $this->currentPage,
            'total' => $this->total,
            'pagesCount' => $this->pagesCount,
            'perPage' => $this->perPage,
            'data' => $data
        ];
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