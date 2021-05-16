<?php

namespace App\DataFixtures;

use App\Entity\Course;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CourseFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $courses = [
            [
                'code' => '1111',
                'type' => 1,
                'cost' => 0,
            ],
            [
                'code' => '1112',
                'type' => 2,
                'cost' => 150,
            ],
            [
                'code' => '1113',
                'type' => 3,
                'cost' => 5000,
            ],
            [
                'code' => '1114',
                'type' => 2,
                'cost' => 300,
            ],
        ];

        // Запись объектов
        foreach ($courses as $course) {
            // Создание курса
            $newCourse = new Course();
            $newCourse->setCode($course['code']);
            $newCourse->setType($course['type']);
            if (isset($course['cost'])) {
                $newCourse->setCost($course['cost']);
            }
            $manager->persist($newCourse);
        }
        $manager->flush();
    }
}
