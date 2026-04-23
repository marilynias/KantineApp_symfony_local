<?php

namespace Shared\Repository;

use DateTime;
use Shared\Entity\Costumer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * @extends ServiceEntityRepository<Costumer>
 */
class CostumerRepository extends ServiceEntityRepository
{
    private const MONTHS_INACTIVE_UNTIL_REMOVAL = 8;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Costumer::class);
    }

    private function getOldInactive(): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.active = false')
            ->andWhere('c.enddate < :date')
            ->setParameter('date', new \DateTimeImmutable(-self::MONTHS_INACTIVE_UNTIL_REMOVAL . ' months'));
    }

    public function countOldInactive(): int
    {
        return $this->getOldInactive()->select('COUNT(c.id)')->getQuery()->getSingleScalarResult();
    }

    public function deleteOldInactive(): int
    {
        return $this->getOldInactive()->delete()->getQuery()->execute();
    }

    public function findByCode($id): Query
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.id = :id')
            ->setParameter('id', $id)
            ->getQuery();
    }

    private function guessKey(string $key): string
    {
        $cleanKey = trim(strtolower($key));
        if ($cleanKey == "id")
            return "id";
        else if ($cleanKey == "active")
            return "active";
        else if ($cleanKey == "firstname")
            return "firstname";
        else if ($cleanKey == "lastname")
            return "lastname";
        else if ($cleanKey == "department")
            return "Department";
        else if ($cleanKey == "enddate")
            return "enddate";
        else
            return $key;
    }

    public function filterBy(array $filters): Query
    {
        $qb = $this->createQueryBuilder('c');
        foreach ($filters as $key => $value) {
            // forgiving key:
            $key = $this->guessKey($key);

            // check if it should be treated as a literal or if it should be treated as a string
            $isLiteral = (ctype_digit($value) && $key == 'id') ||          // numeric id
                filter_var($value, FILTER_VALIDATE_BOOLEAN); // boolean


            // escape unescaped quotes not at beginning/end and stringify if not literal
            if (!$isLiteral) {
                // escape non-start/end ' with "
                $value = preg_replace("/(?<!\\\\|^)'(?!$)/", '"', $value);
                // already stringified (starts and ends with ' -- " does not work)
                $value = preg_replace("/^(?!')|(?<![^\\\\]')$/", "'", $value);
            }
            $qb->andWhere(sprintf('c.%s = %s', $key, $value));
        }
        return $qb->orderBy('c.id', 'ASC')->getQuery();
    }

    #[When(env: 'test')]
    public function getRandomCostumer(): Costumer
    {
        $count = $this->createQueryBuilder('c')
            ->select('count(c)')
            ->getQuery()->getSingleScalarResult();
        $qb = $this->createQueryBuilder('c')
            ->setFirstResult(rand(0,$count-1))
            ->setMaxResults(1)
            ->getQuery();

        return $qb->getSingleResult();
    }

    #[When(env: 'test')]
    public function getAll(): array{
        $qb = $this->createQueryBuilder('c')
            ->select('c')
            ->getQuery();
        return $qb->getResult();
    }

    #[When(env: 'test')]
    public function countAll(){
        $qb = $this->createQueryBuilder('c')
            ->select('count(c)')
            ->getQuery();
        return $qb->getSingleScalarResult();
    }

    #[When(env: 'test')]
    public function getRandomCostumerNotOrdered(): ?Costumer
    {
        $day_start = new DateTime()->setTime(0, 0);
        $qb = $this->createQueryBuilder('c')
            ->where('count(c.orders) = 0')
            // ->innerJoin('c.orders', 'o')
            // ->where('o.Costumer = c.id')
            // ->andWhere('o.order_dateTime < :date')
            
            ->setParameter('date', $day_start)
            ->setMaxResults(1)
            ->getQuery();
            
            // ->andWhere('c.enddate < :date')
            // ->setParameter('date', new \DateTimeImmutable(-self::MONTHS_INACTIVE_UNTIL_REMOVAL . ' months'));
        return $qb->getOneOrNullResult();
    }
}
