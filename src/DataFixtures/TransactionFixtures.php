<?php

namespace App\DataFixtures;

use App\Entity\Course;
use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TransactionFixtures extends Fixture
{
    /**
     * @throws \Exception
     */
    public function load(ObjectManager $manager)
    {
        $courseRepository = $manager->getRepository(Course::class);
        $userRepository = $manager->getRepository(User::class);
        // Получаем пользователя
        $user = $userRepository->findOneBy(['email' => 'artem@mail.ru']);
        $user1 = $userRepository->findOneBy(['email' => 'vika@mail.ru']);
        // Получаем существующие курсы
        $Courses = $courseRepository->findAll();
        /*$rentCourses = $courseRepository->findBy(['type' => 1]);
        $buyCourses = $courseRepository->findBy(['type' => 3]);*/

        $transactions = [
            [
                'type' => 1,
                'value' => $Courses[0]->getCost(),
                'course' => $Courses[0],
                'billingUser' => $user,
                'createdAt' => new \DateTime('2020-04-04 00:00:00'),
            ],
            [
                'type' => 1,
                'value' => $Courses[0]->getCost(),
                'course' => $Courses[0],
                'billingUser' => $user1,
                'createdAt' => new \DateTime('2020-04-04 00:00:00'),
            ],
            // Арендованные курс, у которых закончился срок аренды
            [
                'type' => 1,
                'value' => $Courses[1]->getCost(),
                'validityPeriod' => new \DateTime('2021-05-19 00:00:00'),
                'course' => $Courses[1],
                'billingUser' => $user,
                'createdAt' => new \DateTime('2020-05-05 00:00:00'),
            ],
            [
                'type' => 1,
                'value' => $Courses[1]->getCost(),
                'validityPeriod' => new \DateTime('2021-05-19 00:00:00'),
                'course' => $Courses[1],
                'billingUser' => $user1,
                'createdAt' => new \DateTime('2020-05-05 00:00:00'),
            ],
            // Арендованные курс
            [
                'type' => 1,
                'value' => $Courses[1]->getCost(),
                'validityPeriod' => new \DateTime('2022-11-04 00:00:00'),
                'course' => $Courses[1],
                'billingUser' => $user,
                'createdAt' => new \DateTime('2021-05-04 00:00:00'),
            ],
            [
                'type' => 1,
                'value' => $Courses[3]->getCost(),
                'validityPeriod' => new \DateTime('2022-05-05 00:00:00'),
                'course' => $Courses[3],
                'billingUser' => $user1,
                'createdAt' => new \DateTime('2020-05-05 00:00:00'),
            ],
            // Покупка
            [
                'type' => 1,
                'value' => $Courses[2]->getCost(),
                'course' => $Courses[2],
                'billingUser' => $user,
                'createdAt' => new \DateTime('2021-05-04 00:00:00'),
            ],
            [
                'type' => 1,
                'value' => $Courses[2]->getCost(),
                'course' => $Courses[2],
                'billingUser' => $user1,
                'createdAt' => new \DateTime('2020-05-05 00:00:00'),
            ],
            // Пополнение счета
            [
                'type' => 2,
                'value' => 500,
                'course' => null,
                'billingUser' => $user,
                'createdAt' => new \DateTime('2021-05-06 00:00:00'),
            ],
            [
                'type' => 2,
                'value' => 400,
                'course' => null,
                'billingUser' => $user,
                'createdAt' => new \DateTime('2020-07-07 00:00:00'),
            ],
            [
                'type' => 2,
                'value' => 300,
                'course' => null,
                'billingUser' => $user1,
                'createdAt' => new \DateTime('2020-07-07 00:00:00'),
            ],
        ];

        // Запись объектов
        foreach ($transactions as $transaction) {
            $Transaction = new Transaction();
            $Transaction->setType($transaction['type']);
            $Transaction->setCourse($transaction['course']);
            $Transaction->setBillingUser($transaction['billingUser']);
            $Transaction->setCreatedAt($transaction['createdAt']);
            $Transaction->setValue($transaction['value']);
            if (isset($transaction['validityPeriod'])) {
                $Transaction->setValidityPeriod($transaction['validityPeriod']);
            }
            $manager->persist($Transaction);
        }

        $manager->flush();
    }
}
