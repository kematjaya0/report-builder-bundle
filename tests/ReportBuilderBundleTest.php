<?php

namespace Kematjaya\ReportBuilderBundle\Tests;

use Kematjaya\ReportBuilderBundle\ReportBuilderBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Kematjaya\ReportBuilderBundle\DependencyInjection\ReportBuilderExtension;

class ReportBuilderBundleTest extends TestCase
{
    public function testBundle(): void
    {
        $bundle = new ReportBuilderBundle();
        $this->assertInstanceOf(Bundle::class, $bundle);
        $this->assertInstanceOf(ReportBuilderExtension::class, $bundle->getContainerExtension());
    }

    public function testExtensionLoadsProperly(): void
    {
        $extension = new ReportBuilderExtension();
        $container = new ContainerBuilder();
        
        $extension->load([], $container);
        
        // Assert some default config/service parameter exists or just that no exception was thrown
        $this->assertTrue(true, 'Extension loaded without exceptions.');
    }
}
