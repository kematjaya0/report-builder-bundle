<?php

namespace Kematjaya\ReportBuilderBundle\Tests\Controller;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Kematjaya\ReportBuilderBundle\Controller\ReportBuilderController;
use Kematjaya\ReportBuilderBundle\Manager\ReportBuilderManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Kematjaya\ReportBuilderBundle\Repository\ReportQueryRepository;
use Kematjaya\ReportBuilderBundle\Entity\ReportQuery;
use Kematjaya\ReportBuilderBundle\Form\ReportQueryType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ReportBuilderControllerTest extends TestCase
{
    private MockObject|ReportBuilderManagerInterface $manager;
    private MockObject|EntityManagerInterface $em;
    private MockObject|ReportQueryRepository $repository;
    private MockObject|ReportBuilderController $controller;

    protected function setUp(): void
    {
        $this->manager = $this->createMock(ReportBuilderManagerInterface::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(ReportQueryRepository::class);

        // We use partial mocking for AbstractController methods.
        $this->controller = $this->getMockBuilder(ReportBuilderController::class)
            ->setConstructorArgs([$this->manager, $this->em, $this->repository])
            ->onlyMethods([
                'render', 'createForm', 'generateUrl', 'addFlash', 'redirectToRoute',
                'isCsrfTokenValid', 'json', 'createNotFoundException', 'isGranted', 'createAccessDeniedException'
            ])
            ->getMock();
    }

    public function testIndex(): void
    {
        $queries = [new ReportQuery(), new ReportQuery()];
        $this->repository->expects($this->once())->method('findAll')->willReturn($queries);
        $this->repository->expects($this->exactly(2))->method('countByEnabled')
            ->willReturnCallback(function($enabled) {
                return $enabled ? 5 : 2;
            });

        $expectedResponse = new Response('render');
        $this->controller->expects($this->once())
            ->method('render')
            ->with('@ReportBuilder/report_builder/index.html.twig', [
                'queries' => $queries,
                'total_enabled' => 5,
                'total_disabled' => 2
            ])
            ->willReturn($expectedResponse);

        $response = $this->controller->index();
        $this->assertSame($expectedResponse, $response);
    }

    public function testNewInitial(): void
    {
        $request = new Request();
        $form = $this->createMock(FormInterface::class);
        $formView = $this->createMock(FormView::class);
        $form->method('createView')->willReturn($formView);

        $this->controller->expects($this->once())->method('generateUrl')
            ->with('report_builder_new')->willReturn('/new');
        
        $this->controller->expects($this->once())->method('createForm')
            ->with(ReportQueryType::class, $this->isInstanceOf(ReportQuery::class), ['action' => '/new'])
            ->willReturn($form);

        $form->expects($this->once())->method('handleRequest')->with($request);
        $form->expects($this->once())->method('isSubmitted')->willReturn(false);

        $expectedResponse = new Response('form');
        $this->controller->expects($this->once())->method('render')
            ->with('@ReportBuilder/report_builder/form.html.twig', $this->callback(function($args) use ($formView) {
                return $args['form'] === $formView && $args['is_edit'] === false;
            }))
            ->willReturn($expectedResponse);

        $response = $this->controller->new($request);
        $this->assertSame($expectedResponse, $response);
    }

    public function testNewSubmitSuccess(): void
    {
        $request = new Request();
        $form = $this->createMock(FormInterface::class);

        $this->controller->method('generateUrl')->willReturn('/new');
        $this->controller->method('createForm')->willReturnCallback(function($type, $data, $options) use ($form) {
            if ($data instanceof ReportQuery) {
                $data->setName('test');
            }
            return $form;
        });

        $form->method('handleRequest');
        $form->expects($this->once())->method('isSubmitted')->willReturn(true);
        $form->expects($this->once())->method('isValid')->willReturn(true);

        $this->manager->expects($this->once())->method('generateSlug')->willReturn('test-slug');
        $this->repository->expects($this->once())->method('findBySlug')->with('test-slug')->willReturn(new ReportQuery());

        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(ReportQuery::class));
        $this->em->expects($this->once())->method('flush');

        $this->controller->expects($this->once())->method('addFlash')->with('success', 'Query laporan berhasil disimpan!');
        
        $expectedResponse = new RedirectResponse('/redirect');
        $this->controller->expects($this->once())->method('redirectToRoute')->with('report_builder_index')
            ->willReturn($expectedResponse);

        $response = $this->controller->new($request);
        $this->assertSame($expectedResponse, $response);
    }

    public function testEditSubmitSuccess(): void
    {
        $request = new Request();
        $reportQuery = new ReportQuery();
        $form = $this->createMock(FormInterface::class);

        $this->controller->method('generateUrl')->willReturn('/edit');
        $this->controller->method('createForm')->willReturn($form);

        $form->method('handleRequest');
        $form->expects($this->once())->method('isSubmitted')->willReturn(true);
        $form->expects($this->once())->method('isValid')->willReturn(true);

        $this->em->expects($this->once())->method('flush');

        $this->controller->expects($this->once())->method('addFlash')->with('success', 'Query laporan berhasil diperbarui!');
        
        $expectedResponse = new RedirectResponse('/redirect');
        $this->controller->expects($this->once())->method('redirectToRoute')->with('report_builder_index')
            ->willReturn($expectedResponse);

        $response = $this->controller->edit($request, $reportQuery);
        $this->assertSame($expectedResponse, $response);
    }

    public function testDeleteInvalidCsrf(): void
    {
        $request = new Request([], ['_token' => 'invalid']);
        $reportQuery = new ReportQuery();

        $this->controller->expects($this->once())->method('isCsrfTokenValid')->willReturn(false);
        $this->em->expects($this->never())->method('remove');

        $expectedResponse = new RedirectResponse('/redirect');
        $this->controller->expects($this->once())->method('redirectToRoute')->with('report_builder_index')
            ->willReturn($expectedResponse);

        $response = $this->controller->delete($request, $reportQuery);
        $this->assertSame($expectedResponse, $response);
    }

    public function testDeleteValidCsrf(): void
    {
        $request = new Request([], ['_token' => 'valid']);
        $reportQuery = new ReportQuery();

        $this->controller->expects($this->once())->method('isCsrfTokenValid')->willReturn(true);
        $this->em->expects($this->once())->method('remove')->with($reportQuery);
        $this->em->expects($this->once())->method('flush');

        $this->controller->expects($this->once())->method('addFlash');
        $expectedResponse = new RedirectResponse('/redirect');
        $this->controller->expects($this->once())->method('redirectToRoute')->willReturn($expectedResponse);

        $response = $this->controller->delete($request, $reportQuery);
        $this->assertSame($expectedResponse, $response);
    }

    public function testPreviewInvalid(): void
    {
        $request = new Request([], ['sql' => 'SELECT *', 'params' => '[]']);
        
        $this->manager->expects($this->once())->method('validateQuery')
            ->willReturn(['valid' => false, 'message' => 'Invalid']);
            
        $expectedResponse = new JsonResponse([], 422);
        $this->controller->expects($this->once())->method('json')
            ->with(['success' => false, 'message' => 'Invalid'], 422)
            ->willReturn($expectedResponse);

        $response = $this->controller->preview($request);
        $this->assertSame($expectedResponse, $response);
    }

    public function testPreviewValid(): void
    {
        $request = new Request([], ['sql' => 'SELECT *', 'params' => '[]']);
        
        $this->manager->expects($this->once())->method('validateQuery')
            ->willReturn(['valid' => true]);
            
        $this->manager->expects($this->once())->method('resolveParams')
            ->willReturn(['sql' => 'SELECT *', 'bindings' => []]);
            
        $this->manager->expects($this->once())->method('executeQuery')
            ->willReturn(['success' => true, 'data' => []]);

        $expectedResponse = new JsonResponse();
        $this->controller->expects($this->once())->method('json')
            ->with(['success' => true, 'data' => []])
            ->willReturn($expectedResponse);

        $response = $this->controller->preview($request);
        $this->assertSame($expectedResponse, $response);
    }

    public function testDbStructure(): void
    {
        $this->manager->expects($this->once())->method('getDatabaseStructure')->willReturn(['table1']);
        
        $expectedResponse = new JsonResponse();
        $this->controller->expects($this->once())->method('json')
            ->with(['tables' => ['table1']])
            ->willReturn($expectedResponse);

        $response = $this->controller->dbStructure();
        $this->assertSame($expectedResponse, $response);
    }

    public function testViewNotFound(): void
    {
        $this->repository->expects($this->once())->method('findBySlug')->with('not-found')->willReturn(null);
        
        $this->controller->expects($this->once())->method('createNotFoundException')
            ->willReturn(new NotFoundHttpException());
            
        $this->expectException(NotFoundHttpException::class);
        $this->controller->view(new Request(), 'not-found');
    }

    public function testViewAccessDenied(): void
    {
        $reportQuery = new ReportQuery();
        $reportQuery->setRoles(['ROLE_ADMIN']);
        $this->repository->expects($this->once())->method('findBySlug')->willReturn($reportQuery);
        
        $this->controller->expects($this->once())->method('isGranted')->with('ROLE_ADMIN')->willReturn(false);
        $this->controller->expects($this->once())->method('createAccessDeniedException')
            ->willReturn(new AccessDeniedException());
            
        $this->expectException(AccessDeniedException::class);
        $this->controller->view(new Request(), 'slug');
    }

    public function testViewGetExecute(): void
    {
        $reportQuery = new ReportQuery();
        $reportQuery->setSqlQuery('SELECT *');
        
        $this->repository->expects($this->once())->method('findBySlug')->willReturn($reportQuery);
        $this->manager->expects($this->once())->method('resolveParams')
            ->willReturn(['sql' => 'SELECT *', 'bindings' => []]);
        $this->manager->expects($this->once())->method('executeQuery')
            ->willReturn(['success' => true, 'data' => []]);
            
        $expectedResponse = new Response('render');
        $this->controller->expects($this->once())->method('render')
            ->willReturn($expectedResponse);

        $response = $this->controller->view(new Request(), 'slug');
        $this->assertSame($expectedResponse, $response);
    }

    public function testExportCsvFailed(): void
    {
        $reportQuery = new ReportQuery();
        $reportQuery->setSqlQuery('SELECT *');
        $reportQuery->setQueryParams([]);
        $this->repository->expects($this->once())->method('findBySlug')->willReturn($reportQuery);
        
        $this->manager->expects($this->once())->method('resolveParams')
            ->willReturn(['sql' => '', 'bindings' => []]);
        $this->manager->expects($this->once())->method('executeQuery')
            ->willReturn(['success' => false, 'message' => 'Err']);
            
        $this->controller->expects($this->once())->method('addFlash');
        $expectedResponse = new RedirectResponse('/redirect');
        $this->controller->expects($this->once())->method('redirectToRoute')->willReturn($expectedResponse);

        $response = $this->controller->exportCsv(new Request(), 'slug');
        $this->assertSame($expectedResponse, $response);
    }

    public function testExportCsvSuccess(): void
    {
        $reportQuery = new ReportQuery();
        $reportQuery->setSqlQuery('SELECT *');
        $reportQuery->setQueryParams([]);
        $this->repository->expects($this->once())->method('findBySlug')->willReturn($reportQuery);
        
        $this->manager->expects($this->once())->method('resolveParams')
            ->willReturn(['sql' => '', 'bindings' => []]);
        $this->manager->expects($this->once())->method('executeQuery')
            ->willReturn(['success' => true, 'columns' => ['A'], 'data' => [['A' => 1]]]);

        $response = $this->controller->exportCsv(new Request(), 'slug');
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename=', $response->headers->get('Content-Disposition'));
    }
}
