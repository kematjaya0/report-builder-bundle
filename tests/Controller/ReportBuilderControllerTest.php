<?php

namespace Kematjaya\ReportBuilderBundle\Tests\Controller;

use PHPUnit\Framework\TestCase;
use Kematjaya\ReportBuilderBundle\Controller\ReportBuilderController;
use Kematjaya\ReportBuilderBundle\Manager\ReportBuilderManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Kematjaya\ReportBuilderBundle\Repository\ReportQueryRepository;

class ReportBuilderControllerTest extends TestCase
{
    public function testInstantiation(): void
    {
        $manager = $this->createMock(ReportBuilderManagerInterface::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(ReportQueryRepository::class);

        $controller = new ReportBuilderController($manager, $em, $repository);
        
        $this->assertInstanceOf(ReportBuilderController::class, $controller);
    }
}
