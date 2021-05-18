<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\User;
use App\Model\CourseDto;
use App\Repository\CourseRepository;
use App\Service\PaymentService;
use JMS\Serializer\SerializerInterface;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/api/v1/courses")
 */
class CourseController extends AbstractController
{
    private const TYPES = [
        1 => 'free',
        2 => 'rent',
        3 => 'buy',
    ];
    private const TYPES_EDIT = [
        'free' => 1,
        'rent' => 2,
        'buy' => 3,
    ];

    /**
     * @OA\Get(
     *     path="/api/v1/courses/",
     *     tags={"Course"},
     *     summary="Get all course",
     *     description="Get all course",
     *     operationId="courses.index",
     *     @OA\Response(
     *          response="200",
     *          description="successful operation",
     *          @OA\JsonContent(
     *              type="array",
     *              @OA\Items(
     *                  @OA\Property(
     *                      property="code",
     *                      type="string",
     *                      example="landshaftnoe-proektirovanie"
     *                  ),
     *                  @OA\Property(
     *                      property="type",
     *                      type="string",
     *                      example="rent"
     *                  ),
     *                  @OA\Property(
     *                      property="price",
     *                      type="number",
     *                      format="float",
     *                      example="99.90"
     *                  ),
     *              )
     *          )
     *     ),
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
     *                  example="Invalid credentials."
     *              )
     *          )
     *     )
     * )
     *
     * @Route("/", name="courses_index", methods={"GET"})
     */
    public function index(SerializerInterface $serializer): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $courseRepository = $entityManager->getRepository(Course::class);

        // Получаем все курсы
        $courses = $courseRepository->findAllCourse();
        foreach ($courses as $course) {
            $courseAll[] = [
                'code' => $course['code'],
                'type' => self::TYPES[$course['type']],
                'cost' => $course['cost'],
                'name' => $course['name'],
            ];
        }

        $response = new Response();
        // Устанавливаем статус ответа
        $response->setStatusCode(Response::HTTP_OK);
        // Устанавливаем содержание ответа
        $response->setContent($serializer->serialize($courseAll, 'json'));
        // Устанавливаем заголовок
        $response->headers->add(['Content-Type' => 'application/json']);

        return $response;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/courses/{code}",
     *     tags={"Course"},
     *     summary="Get course by code",
     *     description="Get course by code",
     *     operationId="courses.show",
     *     @OA\Response(
     *          response="200",
     *          description="successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="code",
     *                  type="string",
     *                  example="landshaftnoe-proektirovanie"
     *              ),
     *              @OA\Property(
     *                  property="type",
     *                  type="integer",
     *                  example="rent"
     *              ),
     *              @OA\Property(
     *                  property="price",
     *                  type="number",
     *                  format="float",
     *                  example="99.90"
     *              ),
     *          )
     *     ),
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
     *                  example="Invalid credentials."
     *              )
     *          )
     *     )
     * )
     *
     * @Route("/{code}", name="courses_show", methods={"GET"})
     *
     * @param   string               $code
     * @param   SerializerInterface  $serializer
     *
     * @return Response
     */
    public function show(string $code, SerializerInterface $serializer): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $courseRepository = $entityManager->getRepository(Course::class);

        // Поиск курса
        $course = $courseRepository->findOneBy(['code' => $code]);
        //dump($course);
        if (!isset($course)) {
            $courseData = [
                'code' => Response::HTTP_NOT_FOUND,
                'message' => 'Данный курс не найден',
            ];
            $statusCode = Response::HTTP_NOT_FOUND;
        } else {
            $courseData = [
                'code' => $course->getCode(),
                'type' => $course->getType(),
                'cost' => $course->getCost(),
                'name' => $course->getName(),
            ];
            $statusCode = Response::HTTP_OK;
        }

        $response = new Response();
        // Устанавливаем статус ответа
        $response->setStatusCode($statusCode);
        // Устанавливаем содержание ответа
        $response->setContent($serializer->serialize($courseData, 'json'));
        // Устанавливаем заголовок
        $response->headers->add(['Content-Type' => 'application/json']);

        return $response;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/courses/{code}/pay",
     *     tags={"Course"},
     *     summary="Pay course",
     *     description="Pay course",
     *     operationId="courses.pay",
     *     @OA\Response(
     *          response="200",
     *          description="successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="success",
     *                  type="bool",
     *                  example="true"
     *              ),
     *              @OA\Property(
     *                  property="course_type",
     *                  type="string",
     *                  example="rent"
     *              ),
     *              @OA\Property(
     *                  property="expires_at",
     *                  type="datetime",
     *                  example="2019-05-20T13:46:07+00:00"
     *              ),
     *          )
     *     ),
     *     @OA\Response(
     *          response="406",
     *          description="Недостаточно средств",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="code",
     *                  type="integer",
     *                  example="406"
     *              ),
     *              @OA\Property(
     *                  property="message",
     *                  type="string",
     *                  example="На вашем счете недостаточно средств."
     *              )
     *          )
     *     )
     * )
     *
     * @Route("/{code}/pay", name="courses_pay", methods={"POST"})
     *
     * @return Response
     *
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function coursePay(string $code, SerializerInterface $serializer, PaymentService $paymentService): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $userRepository = $entityManager->getRepository(User::class);
        $courseRepository = $entityManager->getRepository(Course::class);
        try {
            // Поиск курса
            $course = $courseRepository->findOneBy(['code' => $code]);
            if (!isset($course)) {
                throw new \Exception('Такого курса не существует', 401);
            } else {
                // Получаем информацию о пользователе
                $user = $userRepository->findOneBy(['email' => $this->getUser()->getUsername()]);

                $response = new Response();

                // Списываем деньги за курс
                $transaction = $paymentService->payment($user, $course);

                // Формируем ответ
                $data = [
                    'success' => true,
                    'course_type' => $course->getType(),
                    'expires_at' => $transaction->getValidityPeriod(),
                ];
                // Устанавливаем статус ответа

                $response->setStatusCode(Response::HTTP_OK);

                $response->setContent($serializer->serialize($data, 'json'));
                $response->headers->add(['Content-Type' => 'application/json']);

                return $response;
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
            $data = [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];

            // Статус ответа
            $response->setStatusCode($data['code']);
            // Передаем данные
            $response->setContent($serializer->serialize($data, 'json'));
            // Устанавливаем заголовок ( формат json )
            $response->headers->add(['Content-Type' => 'application/json']);

            return $response;
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/courses/new",
     *     tags={"Course"},
     *     summary="Create new course",
     *     description="Create new course",
     *     operationId="courses.new",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/CourseDto")
     *     ),
     *     @OA\Response(
     *          response="201",
     *          description="successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="success",
     *                  type="bool",
     *                  example="true"
     *              )
     *          )
     *     ),
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
     *                  example="Invalid credentials."
     *              )
     *          )
     *     )
     * )
     *
     * @Route("/new", name="courses_new", methods={"POST"})
     *
     * @param   Request              $request
     * @param   SerializerInterface  $serializer
     * @param   ValidatorInterface   $validator
     *
     * @return Response
     */
    public function new(Request $request, SerializerInterface $serializer, ValidatorInterface $validator): Response
    {
        // Десериализация запроса в Dto
        $courseDto = $serializer->deserialize($request->getContent(), CourseDto::class, 'json');
        // Проверка ошибок валидации
        $errors = $validator->validate($courseDto);

        $response = new Response();

        if (count($errors) > 0) {
            // Формируем ответ сервера
            $data = [
                'code' => Response::HTTP_BAD_REQUEST,
                'message' => $errors,
            ];
            // Устанавливаем статус ответа
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
        } else {
            try {
                // Создаем курс из Dto
                $course = Course::fromDto($courseDto);

                $entityManager = $this->getDoctrine()->getManager();

                // Сохраняем курс в базе данных
                $entityManager->persist($course);
                $entityManager->flush();

                // Формируем ответ сервера
                $data = [
                    'success' => true,
                ];
                $response->setStatusCode(Response::HTTP_CREATED);
            } catch (\Exception $e) {
                // Формируем ответ сервера
                $data = [
                    'code' => Response::HTTP_BAD_REQUEST,
                    'message' => $e->getMessage(),
                ];
                $response->setStatusCode(Response::HTTP_CREATED);
            }
        }

        $response->setContent($serializer->serialize($data, 'json'));
        $response->headers->add(['Content-Type' => 'application/json']);

        return $response;
    }

    /**
     * @OA\Post(
     *     tags={"Course"},
     *     path="/api/v1/courses/{code}/edit",
     *     summary="Course edit",
     *     description="Course edit",
     *     security={
     *         { "Bearer":{} },
     *     },
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/CourseDto")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Курс изменен",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="code",
     *                     type="string",
     *                     example="200"
     *                 ),
     *                 @OA\Property(
     *                     property="success",
     *                     type="bool",
     *                     example="true"
     *                 ),
     *             ),
     *        )
     *     ),
     *     @OA\Response(
     *         response=405,
     *         description="Курс с данным кодом уже существует в системе",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="code",
     *                     type="string",
     *                     example="405"
     *                 ),
     *                 @OA\Property(
     *                     property="message",
     *                     type="string",
     *                     example="Курс с данным кодом уже существует в системе"
     *                 ),
     *             ),
     *        )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Данный курс в системе не найден",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="code",
     *                     type="string",
     *                     example="404"
     *                 ),
     *                 @OA\Property(
     *                     property="message",
     *                     type="string",
     *                     example="Данный курс в системе не найден"
     *                 ),
     *             ),
     *        )
     *     )
     * )
     * @Route("/{code}/edit", name="course_edit", methods={"POST"})
     */
    public function edit(
        string $code,
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        CourseRepository $courseRepository
    ): Response {
        // Десериализация запроса в Dto
        $courseDto = $serializer->deserialize($request->getContent(), CourseDto::class, 'json');
        // Проверка ошибок валидации
        $errors = $validator->validate($courseDto);

        $response = new Response();

        if (count($errors) > 0) {
            // Формируем ответ сервера
            $data = [
                'code' => Response::HTTP_BAD_REQUEST,
                'message' => $errors,
            ];
            // Устанавливаем статус ответа
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
        } else {
            // Получаем существующий курс
            $course = $courseRepository->findOneBy(['code' => $code]);
            // Если курс существует
            if ($course && $course->getCode() != $courseDto->code) {
                $course->setName($courseDto->name);
                $course->setCode($courseDto->code);
                $course->setCost($courseDto->cost);
                $course->setType(self::TYPES_EDIT[$courseDto->type]);

                $entityManager = $this->getDoctrine()->getManager();

                // Сохраняем курс в базе данных
                $entityManager->persist($course);
                $entityManager->flush();

                // Формируем ответ сервера
                $data = [
                    'success' => true,
                ];
                $response->setStatusCode(Response::HTTP_OK);
            } else {
                // Формируем ответ сервера
                $data = [
                    'code' => Response::HTTP_BAD_REQUEST,
                    'message' => 'Произошла ошибка.',
                ];
                $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            }
        }
        $response->setContent($serializer->serialize($data, 'json'));
        $response->headers->add(['Content-Type' => 'application/json']);

        return $response;
    }
}
