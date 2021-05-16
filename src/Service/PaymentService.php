<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\Transaction;
use App\Entity\User;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class PaymentService
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function deposit(User $user, float $cost): void
    {
        $this->em->getConnection()->beginTransaction();
        try {
            // Создаем запись транзакции с типом deposit
            $transaction = new Transaction();
            $transaction->setBillingUser($user);
            $transaction->setType(2);
            $transaction->setCreatedAt(new \DateTime());
            $transaction->setValue($cost);

            // Пополняем счет пользователя
            $balance = $user->getBalance() + $cost;
            $user->setBalance($balance);

            // Сохраняем изменения в бд
            $this->em->persist($transaction);
            $this->em->flush();
            $this->em->commit();
        } catch (\Exception $exception) {
            // В случае ошибки откатываем изменения
            $this->em->rollBack();
            throw new \Exception($exception->getMessage(), $exception->getCode());
        }
    }

    public function payment(User $user, Course $course): Transaction
    {
        $this->em->getConnection()->beginTransaction();
        try {
            if ($user->getBalance() < $course->getCost()) {
                throw new \Exception('На вашем счету недостаточно средств', 406);
            }
            $transaction = new Transaction();
            $transaction->setType(1);
            $transaction->setBillingUser($user);
            $transaction->setCreatedAt(new DateTime());
            $transaction->setValue($course->getCost());

            if ('rent' === $course->getType()) {
                $time = (new DateTime())->add(new DateInterval('P1W')); // 1 неделя
                $transaction->setValidityPeriod($time);
            } else {
                $transaction->setValidityPeriod(null);
            }
            $transaction->setCourse($course);

            $user->setBalance($user->getBalance() - $course->getCost());

            $this->em->persist($transaction);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            throw new \Exception($e->getMessage(), $e->getCode());
        }

        return $transaction;

    }
}
