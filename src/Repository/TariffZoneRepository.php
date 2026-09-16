<?php

namespace App\Repository;

use App\Entity\TariffZone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TariffZone>
 *
 * @method TariffZone|null find($id, $lockMode = null, $lockVersion = null)
 * @method TariffZone|null findOneBy(array $criteria, array $orderBy = null)
 * @method TariffZone[]    findAll()
 * @method TariffZone[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TariffZoneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TariffZone::class);
    }

    public function save(TariffZone $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TariffZone $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
