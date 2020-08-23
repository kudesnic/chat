<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Abstract pagination class
 *
 * @author     Andrew Derevinako <andreyy.derevjanko@gmail.com>
 * @version    1.0
 */
abstract class PaginationServiceAbstract
{
    protected $em;
    protected $objectNormalizer;
    protected $repository;
    protected $total;
    protected $currentPage;
    protected $pagesCount;
    protected $perPage;
    protected $offset;
    protected $normalize = false;

    /**
     * PaginationServiceAbstract constructor.
     *
     * @param EntityManagerInterface $em
     * @param ContainerBagInterface $params
     * @param ObjectNormalizer $objectNormalizer
     */
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
     * @return self
     */
    public function setRepository(string $className):self
    {
        $this->repository = $this->em->getRepository($className);

        return $this;
    }

    /**
     * gets the repository for a class.
     *
     * @return ObjectRepository
     */
    public function getRepository():ObjectRepository
    {
        return $this->repository;
    }

    /**
     * Sets current page
     *
     * @param int|null $page
     * @return void
     */
    protected function setCurrentPage(?int $page):void
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
     * @return void
     */
    protected function setCalculatedParams():void
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
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     * @return array
     */
    public function buildPagination(?array $rows):array
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