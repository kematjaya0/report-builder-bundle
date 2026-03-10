<?php

namespace Kematjaya\ReportBuilderBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Kematjaya\ReportBuilderBundle\Entity\ReportQuery;

/**
 * @extends ServiceEntityRepository<ReportQuery>
 */
class ReportQueryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReportQuery::class);
    }

    /**
     * Ambil semua query yang aktif (untuk sidebar menu)
     */
    public function findAllEnabled(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.isEnabled = :enabled')
            ->setParameter('enabled', true)
            ->orderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Ambil query berdasarkan slug (untuk halaman view)
     */
    public function findBySlug(string $slug): ?ReportQuery
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Count untuk statistik
     */
    public function countByEnabled(bool $enabled): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.isEnabled = :enabled')
            ->setParameter('enabled', $enabled)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
