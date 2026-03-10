<?php

namespace Kematjaya\ReportBuilderBundle\Tests\Entity;

use Kematjaya\ReportBuilderBundle\Entity\ReportQuery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class ReportQueryTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $reportQuery = new ReportQuery();
        
        $this->assertNull($reportQuery->getId());
        
        $reportQuery->setName('Test Report');
        $this->assertSame('Test Report', $reportQuery->getName());
        
        $reportQuery->setSlug('test-report');
        $this->assertSame('test-report', $reportQuery->getSlug());
        
        $reportQuery->setSqlQuery('SELECT * FROM testing');
        $this->assertSame('SELECT * FROM testing', $reportQuery->getSqlQuery());
        
        $params = [['name' => 'tahun', 'type' => 'integer']];
        $reportQuery->setQueryParams($params);
        $this->assertSame($params, $reportQuery->getQueryParams());
        
        $this->assertSame('table', $reportQuery->getOutputType());
        $reportQuery->setOutputType('bar');
        $this->assertSame('bar', $reportQuery->getOutputType());
        
        $roles = ['ROLE_ADMIN'];
        $reportQuery->setRoles($roles);
        $this->assertSame($roles, $reportQuery->getRoles());
        
        $reportQuery->setChartXColumn('year');
        $this->assertSame('year', $reportQuery->getChartXColumn());
        
        $reportQuery->setChartYColumn('total');
        $this->assertSame('total', $reportQuery->getChartYColumn());
        
        $this->assertSame('fa fa-bar-chart', $reportQuery->getIcon());
        $reportQuery->setIcon('fa fa-pie-chart');
        $this->assertSame('fa fa-pie-chart', $reportQuery->getIcon());
        
        $this->assertTrue($reportQuery->isEnabled());
        $reportQuery->setIsEnabled(false);
        $this->assertFalse($reportQuery->isEnabled());
        
        $reportQuery->setDescription('A test report');
        $this->assertSame('A test report', $reportQuery->getDescription());
    }

    public function testLifecycleCallbacks(): void
    {
        $reportQuery = new ReportQuery();
        
        $this->assertNull($reportQuery->getCreatedAt());
        $this->assertNull($reportQuery->getUpdatedAt());
        
        $reportQuery->prePersist();
        
        $this->assertInstanceOf(\DateTimeImmutable::class, $reportQuery->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $reportQuery->getUpdatedAt());
        
        $initialUpdatedAt = $reportQuery->getUpdatedAt();
        
        // Small delay to ensure timestamp differs if precision is high, though sleep isn't ideal in unit tests.
        // We'll just verify preUpdate creates a new instance.
        $reportQuery->preUpdate();
        $this->assertNotSame($initialUpdatedAt, $reportQuery->getUpdatedAt());
    }
}
