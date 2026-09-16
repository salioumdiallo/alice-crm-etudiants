<?php

namespace App\Repository;

use App\Entity\SerpResult;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SerpResult>
 *
 * @method SerpResult|null find($id, $lockMode = null, $lockVersion = null)
 * @method SerpResult|null findOneBy(array $criteria, array $orderBy = null)
 * @method SerpResult[]    findAll()
 * @method SerpResult[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SerpResultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SerpResult::class);
    }

    public function save(SerpResult $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SerpResult $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
