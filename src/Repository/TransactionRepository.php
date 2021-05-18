<?php

namespace App\Repository;

use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Transaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method Transaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method Transaction[]    findAll()
 * @method Transaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    // /**
    //  * @return Transaction[] Returns an array of Transaction objects
    //  */

    public function findByTransactionsUsers($type, $code, $skip_expired, $user, CourseRepository $courseRepository)
    {
        $qb = $this->createQueryBuilder('t')
            ->select('t.id, t.createdAt as created_at,t.type, c.code as course_code, t.value as amount, t.validityPeriod')
            ->leftJoin('t.course', 'c')
            ->andWhere('t.billingUser = :userId')
            ->setParameter('userId', $user->getId())
            ->orderBy('t.createdAt', 'DESC');

        if ($type) {
            $numberType = $type;
            $qb->andWhere('t.type = :type')
                ->setParameter('type', $numberType);
        }
        if ($code) {
            $course = $courseRepository->findOneBy(['code' => $code]);
            $value = $course ? $course->getId() : null;
            $qb->andWhere('t.course = :courseId')
                ->setParameter('courseId', $value);
        }
        if ($skip_expired) {
            $qb->andWhere('t.validityPeriod is null or t.validityPeriod >= :today')
                ->setParameter('today', new \DateTime());
        }

        return $qb->getQuery()->getResult();
    }

    public function findTransactionMonth(User $user): array
    {
        $connect = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT c.name, c.type, count(t.course_id), sum(c.cost)
            FROM transaction t INNER JOIN course c ON c.id = t.course_id
            WHERE t.type = 1 AND t.billing_user_id = :user_id
            AND (t.created_at::date between (now()::date - '1 month'::interval) AND now()::date)
            GROUP BY c.name, c.type, t.course_id
            ";
        $stmt = $connect->prepare($sql);
        $stmt->execute([
            'user_id' => $user->getId(),
        ]);

        return $stmt->fetchAll();
    }

    public function findTransactionRentEnd(User $user): array
    {
        $connect = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT * FROM transaction t
            INNER JOIN course c ON c.id = t.course_id
            WHERE t.type= 1
            AND t.billing_user_id = :user_id
            AND t.validity_period::date = (now()::date + '1 day'::interval)
            ORDER BY t.created_at DESC
            ";
        $stmt = $connect->prepare($sql);
        $stmt->execute([
            'user_id' => $user->getId(),
        ]);

        return $stmt->fetchAll();
    }
}
