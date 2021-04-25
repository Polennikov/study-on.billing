<?php

namespace App\Controller;

use App\Entity\User;
use JMS\Serializer\SerializerInterface;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/v1")
 */
class UserController extends AbstractController
{
    /**
     * @OA\Get(
     *     path="/api/v1/current",
     *     tags={"User"},
     *     summary="Get Current user",
     *     description="Get Current user",
     *     operationId="current",
     *
     *     @OA\Response(
     *      response=200,
     *       description="Success",
     *       @OA\JsonContent(
     *              @OA\Property(
     *                  property="username",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="roles",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="balance",
     *                  type="string"
     *              )
     *          )
     *   ),
     *     @OA\Response(
     *          response="401",
     *          description="Invalid credentials",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="code",
     *                  type="string",
     *                  example="401"
     *              ),
     *              @OA\Property(
     *                  property="message",
     *                  type="string",
     *                  example="Invalid JWT Token"
     *              )
     *          )
     *     )
     *)
     * @Route("/current", name="current", methods={"GET"})
     *
     * @param   SerializerInterface  $serializer
     *
     * @return Response
     */
    public function current(SerializerInterface $serializer): Response
    {
        // Получаем пользователя
        $userJwt = $this->getUser();
        $response = new Response();

        if ($userJwt) {
            $em = $this->getDoctrine()->getManager();
            $userRepository = $em->getRepository(User::class);
            // Получаем информацию о пользователе
            $user = $userRepository->findOneBy(['email' => $userJwt->getUsername()]);
            // Формируем ответ
            $data = [
                'username' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'balance' => $user->getBalance(),
            ];
            $response->setStatusCode(Response::HTTP_OK);
        } else {
            $data = [
                'code' => Response::HTTP_CONFLICT,
                'message' => 'Пользователь не найден!',
            ];
        }

        $response->setContent($serializer->serialize($data, 'json'));
        $response->headers->add(['Content-Type' => 'application/json']);

        return $response;
    }
}
