<?php

namespace App\Tests;

use App\DataFixtures\UserFixtures;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;

class AuthControllerTest extends AbstractTest
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

    // Тесты для регистрации в системе
    public function testSeccessfulRegister(): void
    {
        // Регистрация уже существующего пользователя
        $user = [
            'email' => 'userEmail@mail.ru',
            'password' => 'user_password',
        ];

        // Создание запроса
        $client = self::getClient();
        $client->request(
            'POST',
            $this->basePath . '/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->serializer->serialize($user, 'json')
        );

        // Проверка статуса ответа
        $this->assertResponseCode(Response::HTTP_FORBIDDEN, $client->getResponse());

        // Проверка заголовка ответа
        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type', 'application/json'
        ));

        // Считывание содержимого ответа
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertEquals('Пользователь с таким логином уже существует!', $response['message']);

        // Регистрация пользователя с неверными данными
        $user = [
            'email' => 'user',
            'password' => 'user',
        ];

        // Создание запроса
        $client = self::getClient();
        $client->request(
            'POST',
            $this->basePath . '/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->serializer->serialize($user, 'json')
        );

        // Проверка статуса ответа
        $this->assertResponseCode(Response::HTTP_BAD_REQUEST, $client->getResponse());

        // Проверка заголовка ответа
        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type', 'application/json'
        ));

        // Считывание содержимого ответа
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(2, $response['message']);

        // Регистрация пользователя с корректными данными
        $user = [
            'email' => 'userEmail1@mail.ru',
            'password' => 'user_password',
        ];

        // Создание запроса
        $client = self::getClient();
        $client->request(
            'POST',
            $this->basePath . '/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->serializer->serialize($user, 'json')
        );

        // Проверка статуса ответа
        $this->assertResponseCode(Response::HTTP_CREATED, $client->getResponse());

        // Проверка заголовка ответа
        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type', 'application/json'
        ));

        // Считывание содержимого ответа
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($response['token']);
    }

    // Тесты для авторизации в системе
    public function testSeccessfulAuth(): void
    {
        // Вход с неверным именем пользователя
        $user = [
            'username' => 'userEmail@mail.ruuuu',
            'password' => 'user_password',
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
        $this->assertResponseCode(Response::HTTP_UNAUTHORIZED, $client->getResponse());

        // Проверка заголовка ответа
        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type', 'application/json'
        ));

        // Считывание содержимого ответа
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($response['message']);

        // Вход с неверным паролем пользователя
        $user = [
            'username' => 'userEmail@mail.ru',
            'password' => 'user_passwordddddd',
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
        $this->assertResponseCode(Response::HTTP_UNAUTHORIZED, $client->getResponse());

        // Проверка заголовка ответа
        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type', 'application/json'
        ));

        // Считывание содержимого ответа
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($response['message']);

        // Вход с правильными данными пользователя
        $user = [
            'username' => 'userEmail@mail.ru',
            'password' => 'user_password',
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

        // Считывание содержимого ответа
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($response['token']);
    }
}
