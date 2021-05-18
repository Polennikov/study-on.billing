<?php

namespace App\Tests;

use App\DataFixtures\AppFixtures;
use App\DataFixtures\CourseFixtures;
use App\DataFixtures\TransactionFixtures;
use App\Entity\Course;
use App\Service\PaymentService;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;

class CourseControllerTest extends AbstractTest
{
    /**
     * @var string
     */
    private $basePath = '/api/v1/courses/';

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function getFixtures(): array
    {
        return [
            new AppFixtures(
                self::$kernel->getContainer()->get('security.password_encoder'),
                self::$kernel->getContainer()->get(PaymentService::class)
            ),
            new CourseFixtures(),
            new TransactionFixtures(),
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = self::$kernel->getContainer()->get('jms_serializer');
    }

    // Авторизация
    public function auth($user): array
    {
        // Создание запроса
        $client = self::getClient();
        $client->request(
            'POST',
            '/api/v1/auth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->serializer->serialize($user, 'json')
        );

        // Проверка содержимого ответа
        return json_decode($client->getResponse()->getContent(), true);
    }

    // Тест получения всех курсов
    public function testGetAllCourses(): void
    {
        $client = self::getClient();
        // Создание запроса на получение всех курсов
        $client->request(
            'GET',
            $this->basePath,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        // Проверка статуса ответа
        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());

        // Проверка заголовка ответа
        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        // Проверка содержимого ответа
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(4, $response);
    }

    // Тест получения информации о курсе
    public function testGetCourse(): void
    {
        // Проверка получения курса c валидными значениями
        $client = self::getClient();

        // Создание запроса на получения курса
        $codeCourse = '1112';
        $client->request(
            'GET',
            $this->basePath . $codeCourse,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        // Проверка статуса ответа
        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());

        // Проверка заголовка ответа (ответ в виде json?)
        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        // Проверка содержимого ответа, курс арендный
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertEquals('rent', $response['type']);

        // Проверка получения несуществующего курса
        // Создание запроса на получения курса
        $codeCourse = '333';
        $client->request(
            'GET',
            $this->basePath . $codeCourse,
            [],
            [],
            [
                    'CONTENT_TYPE' => 'application/json',
                ]
        );
        // Проверка статуса ответа
        $this->assertResponseCode(Response::HTTP_NOT_FOUND, $client->getResponse());
    }

    // Тест покупки курса
    public function testPayCourse(): void
    {
        // Проверка покупки курса c валидными значениями
        // Авторизация
        $user = [
            'username' => 'artem@mail.ru',
            'password' => 'Artem48',
        ];
        $userData = $this->auth($user);

        $client = self::getClient();

        // Создание запроса для оплаты курса
        $codeCourse = '1112';
        $client->request(
            'POST',
            $this->basePath . $codeCourse . '/pay',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['token'],
            ]
        );
        // Проверка статуса ответа
        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());

        // Проверка заголовка ответа
        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        // Проверка содержимого ответа
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertTrue($response['success']);

        // Проверка покупки курса c недостаточным балансом
        // Создание запроса для оплаты курса, у которого цена больше баланса
        $codeCourse = '1113';
        $client->request(
            'POST',
            $this->basePath . $codeCourse . '/pay',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['token'],
            ]
        );
        // Проверка статуса ответа
        $this->assertResponseCode(Response::HTTP_NOT_ACCEPTABLE, $client->getResponse());

        // Проверка покупки курса c невалидным токеном
        $token = '123';
        $client->request(
            'POST',
            $this->basePath . $codeCourse . '/pay',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ]
        );
        // Проверка статуса ответа
        $this->assertResponseCode(Response::HTTP_UNAUTHORIZED, $client->getResponse());
    }

    // Тест создания нового курса
    public function testNewCourse(): void
    {
        // Проверка создания курса c валидными значениями
        // Авторизация
        $user = [
            'username' => 'admin@mail.ru',
            'password' => 'Admin48',
        ];
        $userData = $this->auth($user);
        // Описание нового курса
        $course = [
            'type' => 'rent',
            'name' => 'высшая математика',
            'code' => '1119',
            'cost' => '100',
        ];

        $client = self::getClient();
        // Создание запроса на создание курса
        $client->request(
            'POST',
            $this->basePath . 'new',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['token'],
            ],
            $this->serializer->serialize($course, 'json')
        );
        // Проверка статуса ответа
        $this->assertResponseCode(Response::HTTP_CREATED, $client->getResponse());

        // Проверка заголовка ответа (ответ в виде json?)
        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type', 'application/json'
        ));

        // Проверка содержимого ответа (успешная операция)
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertTrue($response['success']);

        // Проверка создания курса c не валидными значениями повторяющийся код
        // Авторизация
        $user = [
            'username' => 'admin@mail.ru',
            'password' => 'Admin48',
        ];
        $userData = $this->auth($user);
        // Описание нового курса
        $course = [
            'type' => 'rent',
            'name' => 'высшая математика',
            'code' => '1119',
            'cost' => '100',
        ];

        $client = self::getClient();
        // Создание запроса на создание курса
        $client->request(
            'POST',
            $this->basePath . 'new',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['token'],
            ],
            $this->serializer->serialize($course, 'json')
        );
        // Проверка статуса ответа
        $this->assertResponseCode(Response::HTTP_CREATED, $client->getResponse());

        // Проверка заголовка ответа (ответ в виде json?)
        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type', 'application/json'
        ));

        // Проверка содержимого ответа (успешная операция)
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertEquals($response['code'], 400);
    }

    // Тест изменения курса
    public function testEditCourse(): void
    {
        // Проверка изменения курса c валидными значениями
        // Авторизация
        $user = [
            'username' => 'admin@mail.ru',
            'password' => 'Admin48',
        ];
        $userData = $this->auth($user);
        // Описание нового курса
        $newCourse = [
            'type' => 'buy',
            'name' => 'Алгебра',
            'code' => '16776',
            'cost' => '300',
        ];

        $client = self::getClient();

        $courseRepository = self::getEntityManager()->getRepository(Course::class);

        $course = $courseRepository->findOneBy(['code' => '1112']);

        // Создание запроса на создание курса
        $client->request(
            'POST',
            $this->basePath . $course->getCode() . '/edit',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['token'],
            ],
            $this->serializer->serialize($newCourse, 'json')
        );

        // Проверка статуса ответа
        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());

        // Проверка заголовка ответа (ответ в виде json?)
        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type', 'application/json'
        ));

        // Проверка содержимого ответа (успешная операция)
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertTrue($response['success']);

        // Проверка изменения курса c не валидными значениями
        // Авторизация
        $user = [
            'username' => 'admin@mail.ru',
            'password' => 'Admin48',
        ];
        $userData = $this->auth($user);
        // Описание нового курса
        $newCourse = [
            'type' => 'buy',
            'name' => 'Алгебра',
            'code' => '1113',
            'cost' => '300',
        ];

        $client = self::getClient();

        $courseRepository = self::getEntityManager()->getRepository(Course::class);

        $course = $courseRepository->findOneBy(['code' => '1113']);

        // Создание запроса на создание курса
        $client->request(
            'POST',
            $this->basePath . $course->getCode() . '/edit',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['token'],
            ],
            $this->serializer->serialize($newCourse, 'json')
        );
        // Проверка статуса ответа
        $this->assertResponseCode(Response::HTTP_BAD_REQUEST, $client->getResponse());

        // Проверка заголовка ответа (ответ в виде json?)
        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type', 'application/json'
        ));

        // Проверка содержимого ответа (успешная операция)
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertEquals($response['code'], 400);
    }
}
