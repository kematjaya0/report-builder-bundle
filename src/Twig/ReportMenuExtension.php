<?php

namespace Kematjaya\ReportBuilderBundle\Twig;

use Kematjaya\ReportBuilderBundle\Builder\ReportMenuBuilderInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension untuk menampilkan menu laporan tersimpan di sidebar
 */
class ReportMenuExtension extends AbstractExtension
{
    public function __construct(
        private ReportMenuBuilderInterface $builder, private Environment $twig
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('report_saved_menus', $this->getReportMenus()),
            new TwigFunction("render_chart_js", [$this, 'renderChartJS'], ['is_safe' => ['html']]),
            new TwigFunction("render_report_builder_css", [$this, 'renderCSS'], ['is_safe' => ['html']]),
            new TwigFunction("render_report_builder_js", [$this, 'renderJS'], ['is_safe' => ['html']])
        ];
    }

    public function getReportMenus(): array
    {
        return $this->builder->build();
    }

    public function renderChartJS():string
    {
        return $this->twig->render('@ReportBuilder/chart_javascript.twig');
    }

    public function renderCSS():string
    {
        return $this->twig->render('@ReportBuilder/stylesheets.twig');
    }

    public function renderJS():string
    {
        return $this->twig->render('@ReportBuilder/javascripts.twig');
    }
}
