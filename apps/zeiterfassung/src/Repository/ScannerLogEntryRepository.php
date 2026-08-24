<?php

namespace Zeiterfassung\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LogLevel;
use Zeiterfassung\Entity\ScannerLogEntry;

/**
 * @extends ServiceEntityRepository<ScannerLogEntry>
 */
class ScannerLogEntryRepository extends ServiceEntityRepository
{
    public const DAYS_UNTIL_REMOVAL = 7;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScannerLogEntry::class);
    }

    private function getOld(): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.level IN (:levels)')
            ->andWhere('c.timeStamp < :date')
            ->setParameter('date', new \DateTimeImmutable(-self::DAYS_UNTIL_REMOVAL . ' days'))
            ->setParameter('levels', [LogLevel::INFO, LogLevel::NOTICE]);
    }

    public function countOld(): int
    {
        return $this->getOld()->select('COUNT(c.id)')->getQuery()->getSingleScalarResult();
    }

    public function deleteOld(): int
    {
        return $this->getOld()->delete()->getQuery()->execute();
    }

//    /**
//     * @return ScannerLogEntry[] Returns an array of ScannerLogEntry objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?ScannerLogEntry
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
