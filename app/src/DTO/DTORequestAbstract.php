<?php
namespace App\DTO;

use App\Interfaces\RequestDTOInterface;
use http\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

class DTORequestAbstract implements RequestDTOInterface
{
    protected $data;
    protected $entity;
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->decodeData();
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

    private function decodeData()
    {

        $data = json_decode($this->request->getContent(), true);
        if(!$data){
            throw new HttpException(400, 'Looks like you specified wrong json request data!');
        }
        $this->data = $data;
        foreach ($this->data as $key => $value){
            $this->{$key} = $value;
        }
    }
}