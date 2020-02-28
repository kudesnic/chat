<?php
namespace App\DTO;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

class DTORequestAbstract implements RequestDTOInterface
{
    protected $data;
    protected $entity;

    public function __construct(Request $request)
    {
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
            if(method_exists($entity, $methodName)){
                $entity->{$methodName}($value);
            }
        }

        return $entity;
    }

    public function getEntity()
    {
        return $this->entity;
    }
}