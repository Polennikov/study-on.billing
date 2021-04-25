<?php

namespace App\Controller;

use App\Entity\User;
use App\Model\UserDto;
use JMS\Serializer\SerializerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/api/v1")
 */
class AuthController extends AbstractController
{
    /**
     * @OA\Post(
     *     path="/api/v1/auth",
     *     tags={"User"},
     *     summary="Authorize",
     *     description="Authorize",
     *     operationId="auth",
     *     @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="username",
     *                  type="string",
     *                  example="userEmail@mail.ru"
     *              ),
     *              @OA\Property(
     *                  property="password",
     *                  type="string",
     *                  example="user_password"
     *              )
     *          )
     *     )
     * )
     * @OA\Parameter(
     *     name="username",
     *     in="query",
     *     description="username (email)",
     *     @OA\Schema(type="string")
     * ),
     * @OA\Parameter(
     *     name="password",
     *     in="query",
     *     description="password",
     *     @OA\Schema(type="string")
     * ),
     * @OA\Response(
     *      response=200,
     *       description="Success",
     *       @OA\JsonContent(
     *              @OA\Property(
     *                  property="token",
     *                  type="string"
     *              )
     *          )
     *   ),
     * @OA\Response(
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
     *                  example="Invalid credentials"
     *              )
     *          )
     *     ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *      @OA\JsonContent(
     *              @OA\Property(
     *                  property="code",
     *                  type="string",
     *                  example="404"
     *              ),
     *              @OA\Property(
     *                  property="message",
     *                  type="string",
     *                  example="not found"
     *              )
     *          )
     *   ),
     * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *      @OA\JsonContent(
     *              @OA\Property(
     *                  property="code",
     *                  type="string",
     *                  example="400"
     *              ),
     *              @OA\Property(
     *                  property="message",
     *                  type="string",
     *                  example="Bad Request"
     *              )
     *          )
     *   ),
     * @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *      @OA\JsonContent(
     *              @OA\Property(
     *                  property="code",
     *                  type="string",
     *                  example="403"
     *              ),
     *              @OA\Property(
     *                  property="message",
     *                  type="string",
     *                  example="Forbidden"
     *              )
     *          )
     *      )
     *
     * @Route("/auth", name="auth", methods={"POST"})
     */
    public function auth(): Response
    {
    }

    /**
     * @OA\Post(
     *     path="/api/v1/register",
     *     tags={"User"},
     *     summary="Registration",
     *     description="Registration ",
     *     operationId="register",
     *       @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="email",
     *                  type="string",
     *                  example="userEmail@mail.ru"
     *              ),
     *              @OA\Property(
     *                  property="password",
     *                  type="string",
     *                  example="user_password"
     *              )
     *          )
     *     )
     * )
     * @OA\Parameter(
     *     name="username",
     *     in="query",
     *     description="username (email)",
     *     @OA\Schema(type="email")
     * ),
     * @OA\Parameter(
     *     name="password",
     *     in="query",
     *     description="password",
     *     @OA\Schema(type="password")
     * ),
     * @OA\Response(
     *          response="201",
     *          description="Register successful",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="token",
     *                  type="string"
     *              )
     *          )
     *     ),
     * @OA\Response(
     *          response="500",
     *          description="The server is not available"
     *     ),
     * @OA\Response(
     *          response="400",
     *          description="Validation error",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="message",
     *                  type="array",
     *                  @OA\Items(
     *                      type="string"
     *                  )
     *              )
     *          )
     *     ),
     * @OA\Response(
     *          response="403",
     *          description="User already exist",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="message",
     *                  type="string"
     *              )
     *          )
     *     )
     *
     * @Route("/register", name="register", methods={"POST"})
     *
     * @param   Request                       $request
     * @param   SerializerInterface           $serializer
     * @param   ValidatorInterface            $validator
     * @param   UserPasswordEncoderInterface  $passwordEncoder
     * @param   JWTTokenManagerInterface      $JWTManager
     *
     * @return Response
     */
    public function register(
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        UserPasswordEncoderInterface $passwordEncoder,
        JWTTokenManagerInterface $JWTManager
    ): Response {
        $entityManager = $this->getDoctrine()->getManager();
        $userRepository = $entityManager->getRepository(User::class);

        $response = new Response();

        // Десериализация запроса в Dto
        $userDto = $serializer->deserialize($request->getContent(), UserDto::class, 'json');
        // Проверка ошибок валидации
        $errors = $validator->validate($userDto);

        // Проверяем существует ли пользователь в системе
        if ($userRepository->findOneBy(['email' => $userDto->email])) {
            // Формируем ответ сервера
            $data = [
                'code' => Response::HTTP_FORBIDDEN,
                'message' => 'Пользователь с таким логином уже существует!',
            ];
            // Устанавливаем статус ответа
            $response->setStatusCode(Response::HTTP_FORBIDDEN);
        } elseif (count($errors) > 0) {
            // Формируем ответ сервера
            $data = [
                'code' => Response::HTTP_BAD_REQUEST,
                'message' => $errors,
            ];
            // Устанавливаем статус ответа
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
        } else {
            // Создаем пользователя из Dto
            $user = User::fromDto($userDto);
            // Хешируем пароль
            $user->setPassword($passwordEncoder->encodePassword(
                $user,
                $user->getPassword()
            ));
            // Сохраняем пользователя в базе данных
            $entityManager->persist($user);
            $entityManager->flush();

            // Формируем ответ сервера
            $data = [
                // Создаем JWT token
                'token' => $JWTManager->create($user),
            ];
            // Устанавливаем статус ответа
            $response->setStatusCode(Response::HTTP_CREATED);
        }

        $response->setContent($serializer->serialize($data, 'json'));
        $response->headers->add(['Content-Type' => 'application/json']);

        return $response;
    }
}
