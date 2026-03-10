<?php

namespace Kematjaya\ReportBuilderBundle\Builder;

use Kematjaya\ReportBuilderBundle\Repository\ReportQueryRepository;
use Symfony\Bundle\SecurityBundle\Security;

class ReportMenuBuilder implements ReportMenuBuilderInterface
{
    public function __construct(
        private ReportQueryRepository $repository,
        private Security $security
    ) {
    }

    public function build(): array
    {
        $menus = [];
        // Hanya tampilkan jika user sudah login
        if (!$this->security->getUser()) {
            return $menus;
        }

        $reports = $this->repository->findAllEnabled();

        foreach ($reports as $report) {
            $roles = $report->getRoles();
            $hasAccess = true;

            if (!empty($roles)) {
                $hasAccess = false;
                foreach ($roles as $role) {
                    if ($this->security->isGranted($role)) {
                        $hasAccess = true;
                        break;
                    }
                }
            }

            if ($hasAccess) {
                $menus[] = [
                    'label' => $report->getName(),
                    'icon' => $report->getIcon(),
                    'group' => 'report_dynamic',
                    'route' => 'report_builder_view',
                    "slug" => $report->getSlug(),
                    'route_params' => ['slug' => $report->getSlug()]
                ];
            }
        }

        return $menus;
    }
}
