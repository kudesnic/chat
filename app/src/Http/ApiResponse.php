<?php
namespace App\Http;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

class ApiResponse extends JsonResponse
{
    /**
     * ApiResponse constructor.
     * @param null $data
     * @param string|null $message
     * @param array $errors
     * @param int $status
     * @param array $headers
     * @param bool $json
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function __construct(
        $data = null,
        int $status = 200,
        string $message = null,
        array $errors = [],
        array $headers = [],
        bool $json = false
    ) {
        parent::__construct($this->format($data, $message, $errors), $status, $headers, $json);
    }

    /**
     * Format the API response.
     *
     * @param null $data
     * @param string|null $message
     * @param array $errors
     * @return array
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    private function format($data = null, string $message = null, array $errors = [])
    {
        if ($data === null){
            $data = new \ArrayObject();
        }

        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $nameConverter = new CamelCaseToSnakeCaseNameConverter();
        $propertyNormalizer = new PropertyNormalizer($classMetadataFactory, $nameConverter);
        $dateTimeNormalizer = new DateTimeNormalizer();
        $serializer = new Serializer([$propertyNormalizer, $dateTimeNormalizer]);
        //if object is a Doctrine Proxy, then load all object into proxy.
        //by default proxy has only primary key value, but any other properties are not populated
        if(is_object($data) && $data instanceof  \Doctrine\Common\Persistence\Proxy){
            $data->__load();
        }

        //select only properties with @Groups("APIGroup") annotation
        $data = $serializer->normalize($data, null, ['groups' => 'APIGroup']);

        $response = [
            'message' => $message,
            'data'    => $data,
        ];
        if ($errors) {
            $response['errors'] = $errors;
        }

        return $response;
    }
}