<?php

namespace App\EventListener;

use App\Exception\ValidationExceptionInterface;
use App\Factory\NormalizerFactory;
use App\Http\ApiResponse;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Event listener that processes such errors as 401, 403, 404 errors and wrap in ApiResponse object
 *
 * @package    Authentication
 * @author     Andrew Derevinako <andreyy.derevjanko@gmail.com>
 * @version    1.0
 */
class ExceptionListener
{
    /**
     * @var NormalizerFactory
     */
    private $normalizerFactory;

    /**
     * ExceptionListener constructor.
     *
     * @param NormalizerFactory $normalizerFactory
     */
    public function __construct(NormalizerFactory $normalizerFactory)
    {
        $this->normalizerFactory = $normalizerFactory;
    }

    /**
     * @param ExceptionEvent $event
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();
        $request   = $event->getRequest();
        if (in_array('application/json', $request->getAcceptableContentTypes())
            && ($exception instanceof ValidationExceptionInterface || $exception instanceof NotFoundHttpException)
        ) {

            $response = $this->createApiResponse($exception);
            $event->setResponse($response);
        }
            //for some reasons JWTDecodeFailureException always has statusCode = 0 ...it is Hardcoded in JWT extension,
            //we need 401
//        } elseif ($exception->getCode() != 500)
//        {
//            $response = new ApiResponse([], 401, $exception->getMessage(), []);
//            $event->setResponse($response);
//        }
    }

    /**
     * Creates the ApiResponse from any Exception
     *
     * @param $exception
     * @return ApiResponse
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    private function createApiResponse( $exception)
    {
        $normalizer = $this->normalizerFactory->getNormalizer($exception);
        $statusCode = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
        try {
            $errors = $normalizer ? $normalizer->normalize($exception) : [];
        } catch (\Exception $e) {
            $errors = [];
        }

        return new ApiResponse([], $statusCode, $exception->getMessage(), $errors);
    }
}
