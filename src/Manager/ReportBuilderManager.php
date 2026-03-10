<?php

namespace Kematjaya\ReportBuilderBundle\Manager;

use Kematjaya\ReportBuilderBundle\Entity\ReportQuery;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;

/**
 * Manager untuk validasi, eksekusi query SQL, dan transformasi data ke ECharts
 *
 * @author Report Builder
 */
class ReportBuilderManager implements ReportBuilderManagerInterface
{
    /**
     * Daftar keyword SQL yang dilarang (hanya SELECT yang diizinkan)
     */
    private const FORBIDDEN_KEYWORDS = [
        'DELETE', 'DROP', 'INSERT', 'UPDATE', 'TRUNCATE',
        'ALTER', 'CREATE', 'EXEC', 'EXECUTE', 'GRANT',
        'REVOKE', 'REPLACE', 'MERGE', 'CALL', 'PRAGMA',
        'ATTACH', 'DETACH', '\$\$',
    ];

    public function __construct(private Connection $connection)
    {
    }

    /**
     * Validasi query: hanya SELECT yang diizinkan
     */
    public function validateQuery(string $sql): array
    {
        $sql = trim($sql);

        // Harus diawali dengan SELECT
        if (!preg_match('/^\s*SELECT\s/i', $sql)) {
            return [
                'valid' => false,
                'message' => 'Query have to start with "SELECT". Only "SELECT" allowed.'
            ];
        }

        // Cek keyword berbahaya
        foreach (self::FORBIDDEN_KEYWORDS as $keyword) {
            if (preg_match('/\b' . $keyword . '\b/i', $sql)) {
                return [
                    'valid' => false,
                    'message' => sprintf('Keyword "%s" not allowed.', $keyword)
                ];
            }
        }

        // Cek comment style yang bisa digunakan untuk bypass
        if (preg_match('/--[^\n]*|\/\*.*?\*\//s', $sql)) {
            return [
                'valid' => false,
                'message' => 'SQL not allowed.'
            ];
        }

        return ['valid' => true, 'message' => 'Query valid.'];
    }

    /**
     * Resolusi parameter dinamis: ganti {{nama_param}} dengan nilai dari request
     * Contoh SQL: SELECT * FROM paket WHERE tahun_anggaran = {{tahun}}
     */
    public function resolveParams(string $sql, ?array $queryParams, Request $request): array
    {
        if (empty($queryParams)) {
            return ['sql' => $sql, 'bindings' => []];
        }

        $bindings = [];
        $paramIndex = 1;

        foreach ($queryParams as $param) {
            $placeholder = '{{' . $param['name'] . '}}';
            if (str_contains($sql, $placeholder)) {
                $value = $request->request->get($param['name'], $param['default'] ?? null);

                // Type casting
                $value = match ($param['type'] ?? 'string') {
                    'integer' => (int) $value,
                    'float' => (float) $value,
                    'boolean' => (bool) $value,
                    default => (string) $value,
                };

                // Ganti placeholder dengan positional parameter DBAL
                $paramKey = 'p' . $paramIndex++;
                $sql = str_replace($placeholder, ':' . $paramKey, $sql);
                $bindings[$paramKey] = $value;
            }
        }

        return ['sql' => $sql, 'bindings' => $bindings];
    }

    /**
     * Eksekusi query dan kembalikan data + nama kolom
     */
    public function executeQuery(string $sql, array $bindings = []): array
    {
        try {
            $stmt = $this->connection->executeQuery($sql, $bindings);
            $rows = $stmt->fetchAllAssociative();

            $columns = !empty($rows) ? array_keys($rows[0]) : [];

            return [
                'success' => true,
                'columns' => $columns,
                'data' => $rows,
                'total' => count($rows),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error eksekusi query: ' . $e->getMessage(),
                'columns' => [],
                'data' => [],
                'total' => 0,
            ];
        }
    }

    /**
     * Ambil struktur database (tabel + kolom + tipe) dari information_schema
     */
    public function getDatabaseStructure(): array
    {
        $sql = "
            SELECT 
                t.table_name, 
                c.column_name, 
                c.data_type,
                t.table_type
            FROM information_schema.tables t
            JOIN information_schema.columns c 
                ON t.table_name = c.table_name 
                AND t.table_schema = c.table_schema
            WHERE t.table_schema = 'public'
                AND t.table_type IN ('BASE TABLE', 'VIEW')
            ORDER BY t.table_name, c.ordinal_position
        ";

        $rows = $this->connection->executeQuery($sql)->fetchAllAssociative();


        $structure = [];
        foreach ($rows as $row) {
            $tableName = $row['table_name'];
            if (!isset($structure[$tableName])) {
                $structure[$tableName] = [
                    'name' => $tableName,
                    'table_type' => $row['table_type'],
                    'columns' => [],
                ];
            }
            $structure[$tableName]['columns'][] = [
                'name' => $row['column_name'],
                'type' => $row['data_type']
            ];
        }


        return array_values($structure);
    }

    /**
     * Build ECharts option dari data hasil query
     */
    public function buildEchartsOption(array $data, ReportQuery $report): array
    {
        $xCol = $report->getChartXColumn();
        $yCol = $report->getChartYColumn();
        $type = $report->getOutputType();

        if (empty($data) || !$xCol || !$yCol) {
            return [];
        }

        $xData = array_column($data, $xCol);
        $yData = array_map(fn($row) => (float)($row[$yCol] ?? 0), $data);

        $baseOption = [
            'tooltip' => ['trigger' => 'axis'],
            'grid' => ['left' => '3%', 'right' => '4%', 'bottom' => '3%', 'containLabel' => true],
            'toolbox' => [
                'feature' => [
                    'saveAsImage' => ['title' => 'Simpan'],
                    'dataView' => ['readOnly' => false, 'title' => 'Data'],
                    'restore' => ['title' => 'Reset'],
                ]
            ],
        ];

        return match ($type) {
            'bar' => array_merge($baseOption, [
                'xAxis' => ['type' => 'category', 'data' => $xData, 'axisLabel' => ['rotate' => 30]],
                'yAxis' => ['type' => 'value'],
                'series' => [['name' => $yCol, 'type' => 'bar', 'data' => $yData, 'itemStyle' => ['borderRadius' => [4, 4, 0, 0]]]],
                'color' => ['#5470c6', '#91cc75', '#fac858', '#ee6666', '#73c0de'],
            ]),
            'line' => array_merge($baseOption, [
                'xAxis' => ['type' => 'category', 'data' => $xData],
                'yAxis' => ['type' => 'value'],
                'series' => [['name' => $yCol, 'type' => 'line', 'data' => $yData, 'smooth' => true, 'areaStyle' => []]],
            ]),
            'pie' => [
                'tooltip' => ['trigger' => 'item', 'formatter' => '{a} <br/>{b}: {c} ({d}%)'],
                'legend' => ['orient' => 'vertical', 'left' => 'left'],
                'toolbox' => $baseOption['toolbox'],
                'series' => [[
                    'name' => $yCol,
                    'type' => 'pie',
                    'radius' => ['40%', '70%'],
                    'data' => array_map(fn($x, $y) => ['name' => $x, 'value' => $y], $xData, $yData),
                    'emphasis' => ['itemStyle' => ['shadowBlur' => 10, 'shadowOffsetX' => 0, 'shadowColor' => 'rgba(0, 0, 0, 0.5)']],
                ]],
            ],
            'scatter' => array_merge($baseOption, [
                'xAxis' => ['type' => 'value'],
                'yAxis' => ['type' => 'value'],
                'series' => [[
                    'name' => $report->getName(),
                    'type' => 'scatter',
                    'data' => array_map(fn($row) => [(float)$row[$xCol], (float)$row[$yCol]], $data),
                    'symbolSize' => 10,
                ]],
            ]),
            default => [],
        };
    }

    /**
     * Generate slug dari nama
     */
    public function generateSlug(string $name): string
    {
        $slug = strtolower($name);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        return trim($slug, '-');
    }
}
