<?php

namespace Zeiterfassung\Controller;

use Shared\Entity\Costumer;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Zeiterfassung\Entity\ScannerLogEntry;

final class ScannerLogController extends AbstractFOSRestController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {}


    // CRON Job for all-inkl
    #[IsGranted('ROLE_CRON')]
    #[Route('/cron/delete_old_logs', name: 'del_logs')]
    public function deleteOldInactiveCostumers(Request $request): Response
    {
        $repository = $this->entityManager->getRepository(ScannerLogEntry::class);
        $count = $repository->deleteOld();

        $res_txt = $count ? sprintf('Deleted %d old Log entries.', $count) : 'No Logs to delete';
        return new Response($res_txt);
    }
}
