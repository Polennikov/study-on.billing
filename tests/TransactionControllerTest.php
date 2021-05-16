<?php

namespace App\Tests;

use App\DataFixtures\AppFixtures;
use App\DataFixtures\CourseFixtures;
use App\DataFixtures\TransactionFixtures;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;

class TransactionControllerTest extends AbstractTest
{
    /**
     * @var string
     */
    private $basePath = '/api/v1/transactions/';

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function getFixtures(): array
    {
        return [
            new AppFixtures(self::$kernel->getContainer()->get('security.password_encoder')),
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

        // Проверка содержимого ответа (В ответе должен быть представлен token)
        return json_decode($client->getResponse()->getContent(), true);
    }

    // Тест истории начислений и списаний текущего пользователя
    public function testTransaction(): void
    {
        //Тест с валидными значениями
        // Авторизация
        $user = [
            'username' => 'artem@mail.ru',
            'password' => 'Artem48',
        ];
        $userData = $this->auth($user);

        $client = self::getClient();
        // Запрос на получение всех транзакций пользователя
        $client->request(
            'GET',
            $this->basePath,
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
        self::assertCount(6, $response);

        // Тест с невалидным токеном
        $token = '123';
        // Создание запроса на получение всех курсов
        $client->request(
            'GET',
            $this->basePath,
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
}
