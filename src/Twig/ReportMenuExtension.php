<?php

namespace Kematjaya\ReportBuilderBundle\Twig;

use Kematjaya\ReportBuilderBundle\Builder\ReportMenuBuilderInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension untuk menampilkan menu laporan tersimpan di sidebar
 */
class ReportMenuExtension extends AbstractExtension
{
    public function __construct(
        private ReportMenuBuilderInterface $builder
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('report_saved_menus', $this->getReportMenus()),
        ];
    }

    public function getReportMenus(): array
    {
        return $this->builder->build();
    }
}
