<?php
namespace App\Security;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class JwtTokenAuthenticator extends AbstractGuardAuthenticator
{
    private $jwtEncoder;
    private $em;

//    public function __construct(JWTEncoderInterface $jwtEncoder, EntityManagerInterface $em)
//    {
//        $this->jwtEncoder = $jwtEncoder;
//        $this->em = $em;
//    }

    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning false will cause this authenticator
     * to be skipped.
     */
    public function supports(Request $request)
    {
        return $request->headers->has('X-AUTH-TOKEN');
    }

    /**
     * Called on every request. Return whatever credentials you want to
     * be passed to getUser() as $credentials.
     * This will cause authentication to stop. Not fail, just stop trying to authenticate the user via this method.
     * If there is a token, return it!
     */
    public function getCredentials(Request $request)
    {
//        $extractor = new AuthorizationHeaderTokenExtractor(
//            'Bearer',
//            'Authorization'
//        );
//        $token = $extractor->extract($request);
//        if (!$token) {
//            return;
//        }
//
//        return $token;
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
//        try {
//            $data = $this->jwtEncoder->decode($credentials);
//        } catch (JWTDecodeFailureException $e) {
//            // if you want to, use can use $e->getReason() to find out which of the 3 possible things went wrong
//            // and tweak the message accordingly
//            // https://github.com/lexik/LexikJWTAuthenticationBundle/blob/05e15967f4dab94c8a75b275692d928a2fbf6d18/Exception/JWTDecodeFailureException.php
//
//            throw new CustomUserMessageAuthenticationException('Invalid Token');
//        }
//
//        $email = $data['email'];
//        return $this->em
//            ->getRepository('AppBundle:Users')
//            ->findOneBy(['email' => $email]);
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        // check credentials - e.g. make sure the password is valid
        // no credential check is needed in this case

        // return true to cause authentication success
        return true;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // on success, let the request continue
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $data = [
            // you may ant to customize or obfuscate the message first
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())

            // or to translate this message
            // $this->translator->trans($exception->getMessageKey(), $exception->getMessageData())
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Called when authentication is needed, but it's not sent
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $data = [
            // you might translate this message
            'message' => 'Authentication Required'
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    public function supportsRememberMe()
    {
        return false;
    }
}
