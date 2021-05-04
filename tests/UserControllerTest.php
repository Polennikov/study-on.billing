<?php

namespace App\Tests;

use App\DataFixtures\UserFixtures;
use App\Entity\User;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends AbstractTest
{
    /**
     * @var string
     */
    private $basePath = '/api/v1';

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function getFixtures(): array
    {
        return [new UserFixtures(self::$kernel->getContainer()->get('security.password_encoder'))];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = self::$kernel->getContainer()->get('jms_serializer');
    }

    public function testCurrentUser(): void
    {
        // Вход пользователя в систему для получения токена
        $user = [
            'username' => 'artem@mail.ru',
            'password' => 'Artem48',
        ];

        // Создание запроса
        $client = self::getClient();
        $client->request(
            'POST',
            $this->basePath . '/auth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->serializer->serialize($user, 'json')
        );

        // Проверка статуса ответа
        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());

        // Проверка заголовка ответа
        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type', 'application/json'
        ));

        // Считывание ответа
        $response = json_decode($client->getResponse()->getContent(), true);
        $token = $response['token'];

        // Проверка получения данных о пользователе
        $client = self::getClient();
        $client->request(
            'GET',
            $this->basePath . '/current',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token,
             'CONTENT_TYPE' => 'application/json', ]
        );
        // Проверка статуса ответа
        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());

        // Проверка заголовка ответа
        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type', 'application/json'
        ));
        // Считывание ответа
        $response = json_decode($client->getResponse()->getContent(), true);
        // Получение имени из ответа
        $userEmail = $response['username'];

        // Получим данные о пользователе из бд и сравним с данными ответа
        $em = self::getEntityManager();
        $user = $em->getRepository(User::class)->findOneBy(['email' => $userEmail]);
        // Проверка пользователя
        self::assertNotEmpty($user);

        // Проверка с неверным токеном
        $token = '123456789';

        $client = self::getClient();
        $client->request(
            'GET',
            $this->basePath . '/current',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token,
             'CONTENT_TYPE' => 'application/json', ]
        );
        // Проверка статуса ответа
        $this->assertResponseCode(Response::HTTP_UNAUTHORIZED, $client->getResponse());

        // Проверка заголовка ответа
        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type', 'application/json'
        ));
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertEquals($response['message'], 'Invalid JWT Token');
    }
}
