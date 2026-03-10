<?php

namespace Kematjaya\ReportBuilderBundle\Manager;

use Kematjaya\ReportBuilderBundle\Entity\ReportQuery;
use Symfony\Component\HttpFoundation\Request;

interface ReportBuilderManagerInterface
{
    public function validateQuery(string $sql): array;
    public function resolveParams(string $sql, ?array $queryParams, Request $request): array;
    public function executeQuery(string $sql, array $bindings = []): array;
    public function getDatabaseStructure(): array;
    public function buildEchartsOption(array $data, ReportQuery $report): array;
    public function generateSlug(string $name): string;
}