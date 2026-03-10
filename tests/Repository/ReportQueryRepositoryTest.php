<?php

namespace Kematjaya\ReportBuilderBundle\Tests\Repository;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Kematjaya\ReportBuilderBundle\Entity\ReportQuery;
use Kematjaya\ReportBuilderBundle\Repository\ReportQueryRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReportQueryRepositoryTest extends TestCase
{
    private MockObject|ManagerRegistry $registry;
    private MockObject|EntityManagerInterface $entityManager;
    private MockObject|ClassMetadata $classMetadata;
    private ReportQueryRepository $repository;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->classMetadata = $this->createMock(ClassMetadata::class);
        $this->classMetadata->name = ReportQuery::class;

        $this->registry->method('getManagerForClass')
            ->with(ReportQuery::class)
            ->willReturn($this->entityManager);

        $this->entityManager->method('getClassMetadata')
            ->with(ReportQuery::class)
            ->willReturn($this->classMetadata);

        $this->repository = new ReportQueryRepository($this->registry);
    }

    public function testFindAllEnabled(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())->method('select')->willReturn($queryBuilder);
        $queryBuilder->expects($this->once())->method('from')->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('r.isEnabled = :enabled')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('enabled', true)
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('r.name', 'ASC')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $expectedResult = [new ReportQuery()];
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedResult);

        $result = $this->repository->findAllEnabled();
        $this->assertSame($expectedResult, $result);
    }

    public function testFindBySlug(): void
    {
        $slug = 'test-slug';
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())->method('select')->willReturn($queryBuilder);
        $queryBuilder->expects($this->once())->method('from')->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('r.slug = :slug')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('slug', $slug)
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $expectedResult = new ReportQuery();
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($expectedResult);

        $result = $this->repository->findBySlug($slug);
        $this->assertSame($expectedResult, $result);
    }

    public function testCountByEnabled(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->exactly(2))->method('select')->willReturn($queryBuilder); // One for default alias, one explicit select('COUNT(r.id)')
        $queryBuilder->expects($this->once())->method('from')->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('r.isEnabled = :enabled')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('enabled', true)
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn(5);

        $result = $this->repository->countByEnabled(true);
        $this->assertSame(5, $result);
    }
}
