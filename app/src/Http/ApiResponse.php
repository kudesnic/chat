<?php
namespace App\Http;

use Symfony\Component\HttpFoundation\JsonResponse;

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