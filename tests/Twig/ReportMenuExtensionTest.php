<?php

namespace Kematjaya\ReportBuilderBundle\Tests\Twig;

use Kematjaya\ReportBuilderBundle\Builder\ReportMenuBuilderInterface;
use Kematjaya\ReportBuilderBundle\Twig\ReportMenuExtension;
use PHPUnit\Framework\TestCase;

class ReportMenuExtensionTest extends TestCase
{
    public function testGetFunctions(): void
    {
        $builder = $this->createMock(ReportMenuBuilderInterface::class);
        $builder->method('build')->willReturn(['menu1' => 'value1']);
        
        $extension = new ReportMenuExtension($builder);
        $functions = $extension->getFunctions();
        
        $this->assertCount(1, $functions);
        $this->assertSame('report_saved_menus', $functions[0]->getName());
        
        $this->assertSame(['menu1' => 'value1'], $extension->getReportMenus());
    }
}
