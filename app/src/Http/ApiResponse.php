<?php
namespace App\Http;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

class ApiResponse extends JsonResponse
{
    /**
     * ApiResponse constructor.
     *
     * @param mixed|null  $data
     * @param string|null $message
     * @param array  $errors
     * @param int    $status
     * @param array  $headers
     * @param bool   $json
     */
    public function __construct(
        $data = null,
        string $message = null,
        array $errors = [],
        int $status = 200,
        array $headers = [],
        bool $json = false
    ) {
        parent::__construct($this->format($data, $message, $errors), $status, $headers, $json);
    }

    /**
     * Format the API response.
     *
     * @param mixed|null  $data
     * @param string|null $message
     * @param array  $errors
     *
     * @return array
     */
    private function format($data = null, string $message = null, array $errors = [])
    {
        if ($data === null) {
            $data = new \ArrayObject();
        }
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        ;
        $nameConverter = new CamelCaseToSnakeCaseNameConverter();
        $normalizer = new PropertyNormalizer($classMetadataFactory, $nameConverter);
        $serializer = new Serializer([$normalizer]);
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