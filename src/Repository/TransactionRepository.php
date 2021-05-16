<?php

namespace App\Repository;

use App\Entity\Transaction;
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

        /*
        public function findOneBySomeField($value): ?Transaction
        {
            return $this->createQueryBuilder('t')
                ->andWhere('t.exampleField = :val')
                ->setParameter('val', $value)
                ->getQuery()
                ->getOneOrNullResult()
            ;
        }
        */
    }
}
