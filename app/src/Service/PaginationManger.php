<?php

namespace App\Service;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

class PaginationManger
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
     * @param array|null $orderBy
     * @param int|null $page
     * @param int|null $perPage
     * @return array
     */
    public function paginate(array $criteria, ?array $orderBy = null, ?int $page = null,  ?int $perPage = null)
    {
        $this->setCurrentPage($page);
        if(is_null($perPage) == false){
            $this->perPage = $perPage;
        }
        $this->setCalculatedParams($criteria);
        $rows = $this->repository->findBy($criteria, $orderBy, $this->perPage, $this->offset);

        return $this->buildPagination($rows);

    }

    /**
     * Builds pagination array for nested set nodes
     *
     * @param $node
     * @param array $criteria
     * @param array|null $orderBy
     * @param int|null $page
     * @param int|null $perPage
     * @return array
     */
    public function paginateNodeChildren($node, ?array $orderBy = null, ?int $page = null,  ?int $perPage = null)
    {
        $this->setCurrentPage($page);
        if(is_null($perPage) == false){
            $this->perPage = $perPage;
        }
        $this->setCalculatedParams([]);
        $rows = $this->repository->findChildrenBy($node, $orderBy, $this->perPage, $this->offset);

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
     * Sets such calculated params, such as total and pagesCount
     *
     * @param $criteria
     */
    private function setCalculatedParams($criteria)
    {
        $this->total = $this->repository->count($criteria);
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

}