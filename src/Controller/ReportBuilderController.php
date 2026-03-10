<?php

namespace Kematjaya\ReportBuilderBundle\Controller;

use Kematjaya\ReportBuilderBundle\Entity\ReportQuery;
use Kematjaya\ReportBuilderBundle\Form\ReportQueryType;
use Kematjaya\ReportBuilderBundle\Manager\ReportBuilderManagerInterface;
use Kematjaya\ReportBuilderBundle\Repository\ReportQueryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ReportBuilderController extends AbstractController
{
    public function __construct(
        private ReportBuilderManagerInterface $manager,
        private EntityManagerInterface $em,
        private ReportQueryRepository $repository,
    ) {
    }

    public function index(): Response
    {
        $queries = $this->repository->findAll();
        $totalEnabled = $this->repository->countByEnabled(true);
        $totalDisabled = $this->repository->countByEnabled(false);

        return $this->render('@ReportBuilder/report_builder/index.html.twig', [
            'queries' => $queries,
            'total_enabled' => $totalEnabled,
            'total_disabled' => $totalDisabled
        ]);
    }

    public function new(Request $request): Response
    {

        $reportQuery = new ReportQuery();
        $form = $this->createForm(ReportQueryType::class, $reportQuery, [
            'action' => $this->generateUrl('report_builder_new'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Generate slug unik
            $slug = $this->manager->generateSlug($reportQuery->getName());
            $existing = $this->repository->findBySlug($slug);
            if ($existing) {
                $slug .= '-' . substr(uniqid(), -4);
            }
            $reportQuery->setSlug($slug);

            $this->em->persist($reportQuery);
            $this->em->flush();

            $this->addFlash('success', 'Query laporan berhasil disimpan!');
            return $this->redirectToRoute('report_builder_index');
        }

        return $this->render('@ReportBuilder/report_builder/form.html.twig', [
            'form' => $form->createView(),
            'report_query' => $reportQuery,
            'is_edit' => false,
        ]);
    }

    public function edit(Request $request, ReportQuery $reportQuery): Response
    {

        $form = $this->createForm(ReportQueryType::class, $reportQuery, [
            'action' => $this->generateUrl('report_builder_edit', ['id' => $reportQuery->getId()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Query laporan berhasil diperbarui!');
            return $this->redirectToRoute('report_builder_index');
        }

        return $this->render('@ReportBuilder/report_builder/form.html.twig', [
            'form' => $form->createView(),
            'report_query' => $reportQuery,
            'is_edit' => true
        ]);
    }


    public function delete(Request $request, ReportQuery $reportQuery): Response
    {
        if ($this->isCsrfTokenValid('delete' . $reportQuery->getId(), $request->request->get('_token'))) {
            $this->em->remove($reportQuery);
            $this->em->flush();
            $this->addFlash('success', 'Query laporan berhasil dihapus.');
        }

        return $this->redirectToRoute('report_builder_index');
    }

    /**
     * AJAX: Preview/eksekusi query SQL
     */

    public function preview(Request $request): JsonResponse
    {
        $sql = $request->request->get('sql', '');
        $paramsJson = $request->request->get('params', '[]');

        // Validasi keamanan
        $validation = $this->manager->validateQuery($sql);
        if (!$validation['valid']) {
            return $this->json(['success' => false, 'message' => $validation['message']], 422);
        }

        // Resolusi parameter
        try {
            $queryParams = json_decode($paramsJson, true, 512, JSON_THROW_ON_ERROR) ?? [];
        } catch (\JsonException) {
            $queryParams = [];
        }

        $resolved = $this->manager->resolveParams($sql, $queryParams, $request);
        $result = $this->manager->executeQuery($resolved['sql'], $resolved['bindings']);

        return $this->json($result);
    }


    public function dbStructure(): JsonResponse
    {
        $structure = $this->manager->getDatabaseStructure();
        return $this->json(['tables' => $structure]);
    }

    public function view(Request $request, string $slug): Response
    {
        $reportQuery = $this->repository->findBySlug($slug);
        if (!$reportQuery) {
            throw $this->createNotFoundException('Laporan tidak ditemukan.');
        }

        $roles = $reportQuery->getRoles();
        if (!empty($roles)) {
            $hasAccess = false;
            foreach ($roles as $role) {
                if ($this->isGranted($role)) {
                    $hasAccess = true;
                    break;
                }
            }
            if (!$hasAccess) {
                throw $this->createAccessDeniedException('Access Denied.');
            }
        }

        $result = null;
        $echartsOption = null;

        if ($request->isMethod('GET') && empty($reportQuery->getQueryParams())) {
            // Auto-execute jika tidak ada parameter
            $resolved = $this->manager->resolveParams(
                $reportQuery->getSqlQuery(),
                $reportQuery->getQueryParams(),
                $request
            );
            $result = $this->manager->executeQuery($resolved['sql'], $resolved['bindings']);
            if ($result['success'] && $reportQuery->getOutputType() !== 'table') {
                $echartsOption = $this->manager->buildEchartsOption($result['data'], $reportQuery);
            }
        } elseif ($request->isMethod('POST')) {
            $validation = $this->manager->validateQuery($reportQuery->getSqlQuery());
            if ($validation['valid']) {
                $resolved = $this->manager->resolveParams(
                    $reportQuery->getSqlQuery(),
                    $reportQuery->getQueryParams(),
                    $request
                );
                $result = $this->manager->executeQuery($resolved['sql'], $resolved['bindings']);
                if ($result['success'] && $reportQuery->getOutputType() !== 'table') {
                    $echartsOption = $this->manager->buildEchartsOption($result['data'], $reportQuery);
                }
            }
        }

        return $this->render('@ReportBuilder/report_builder/view.html.twig', [
            'report_query' => $reportQuery,
            'result' => $result,
            'echarts_option' => $echartsOption
        ]);
    }


    public function exportCsv(Request $request, string $slug): Response
    {
        $reportQuery = $this->repository->findBySlug($slug);
        if (!$reportQuery) {
            throw $this->createNotFoundException('Laporan tidak ditemukan.');
        }

        $roles = $reportQuery->getRoles();
        if (!empty($roles)) {
            $hasAccess = false;
            foreach ($roles as $role) {
                if ($this->isGranted($role)) {
                    $hasAccess = true;
                    break;
                }
            }
            if (!$hasAccess) {
                throw $this->createAccessDeniedException('Access Denied.');
            }
        }

        $resolved = $this->manager->resolveParams(
            $reportQuery->getSqlQuery(),
            $reportQuery->getQueryParams(),
            $request
        );
        $result = $this->manager->executeQuery($resolved['sql'], $resolved['bindings']);

        if (!$result['success']) {
            $this->addFlash('error', 'Gagal mengekspor data: ' . $result['message']);
            return $this->redirectToRoute('report_builder_view', ['slug' => $slug]);
        }

        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $slug . '_' . date('YmdHis') . '.csv"');

        $handle = fopen('php://output', 'w+');
        // Add BOM for Excel UTF-8 compatibility
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

        if (!empty($result['columns'])) {
            fputcsv($handle, $result['columns']);
        }

        foreach ($result['data'] as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);

        return $response;
    }
}
