<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function load(ObjectManager $manager)
    {
        // Создание пользователя с ролью ROLE_USER
        $user = new User();
        $user->setEmail('artem@mail.ru');
        $user->setPassword($this->passwordEncoder->encodePassword(
            $user, 'Artem48'));
        $user->setRoles(['ROLE_USER']);
        $user->setBalance(1000);
        $manager->persist($user);

        $user = new User();
        $user->setEmail('vika@mail.ru');
        $user->setPassword($this->passwordEncoder->encodePassword(
            $user, 'Vika48'));
        $user->setRoles(['ROLE_USER']);
        $user->setBalance(2000);
        $manager->persist($user);

        // Создание пользователя с ролью ROLE_SUPER_ADMIN
        $user = new User();
        $user->setEmail('admin@mail.ru');
        $user->setPassword($this->passwordEncoder->encodePassword(
            $user, 'Admin48'));
        $user->setRoles(['ROLE_SUPER_ADMIN']);
        $user->setBalance(0);
        $manager->persist($user);

        $manager->flush();
    }
}
