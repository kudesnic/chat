<?php
namespace App\DTO;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

class DTORequestAbstract implements RequestDTOInterface
{
    protected $data;
    protected $entity;
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;

        $this->data = json_decode($request->getContent(), true);
        foreach ($this->data as $key => $value){
            $this->{$key} = $value;
        }
    }

    public function populateEntity($entity)
    {
        $nameConverter = new CamelCaseToSnakeCaseNameConverter();
        foreach ($this->data as $key => $value){
            $propertyName = $nameConverter->denormalize($key);
            $methodName = 'set' . $propertyName;
            if(method_exists($entity, $methodName) && property_exists($this, $propertyName)){
                $entity->{$methodName}($value);
            }
        }
        $this->entity = $entity;

        return $entity;
    }

    public function getEntity()
    {
        return $this->entity;
    }

    public function getRequest()
    {
        return $this->request;
    }
}