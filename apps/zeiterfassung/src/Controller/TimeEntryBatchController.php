<?php

namespace Zeiterfassung\Controller;

use IntlDateFormatter;
use Shared\Entity\Costumer;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\Exporter\Handler;
use Sonata\Exporter\Source\ArraySourceIterator;
use Sonata\Exporter\Writer\XlsxWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
// use Symfony\Polyfill\Intl\Icu\IntlDateFormatter;
use Zeiterfassung\Entity\TimeEntry;
use ZipArchive;

final class TimeEntryBatchController extends AbstractController
{
    public function batchGetReportAction(ProxyQueryInterface $query, AdminInterface $admin): BinaryFileResponse|RedirectResponse
    {
        $admin->checkAccess('list');

        $format = datefmt_create('de-DE');
        $format->setPattern("EEEE dd.M.y");
        // $format->setPattern()

        $selectedUsers = $query->execute();
        $data = [];
        $data1 = [];

        foreach ($selectedUsers as $timeEntry) {
            if(!$timeEntry instanceof TimeEntry) continue;
            $month = $timeEntry->getCheckinTime()->format('MM.yyyy');
            $data[$timeEntry->getUser()->getFullname()][$month][] = [
                // 'Datum'=>   $format, $timeEntry->getCheckinTime()->format('ll dd.mm.yyyy'), 
                'Datum'=>   datefmt_format($format, $timeEntry->getCheckinTime()), 
                'Eintrag' => $timeEntry->getCheckinTime()->format('h:m'), 
                'Austrag' => $timeEntry->getCheckoutTime()? $timeEntry->getCheckoutTime()->format('h:m'):''];
            // $data[] = [
            //     'Vorname' => $user->getFirstname(),
            //     'Nachname' => $user->getLastname(),
            // ];
        }

        $zipName = tempnam(sys_get_temp_dir(), 'zip_');
        
        $zip = new ZipArchive();
        
        
        if ($zip->open($zipName, ZipArchive::CREATE) !== true) {
            throw new \RuntimeException(_('Cannot open ' . $zipName));
        }

        foreach ($data as $costumer => $months) {
            // if(!$costumer instanceof Costumer) continue;
            
            $zip->addEmptyDir($costumer);
            foreach ($months as $entries) {
                //     $writer->write($value);
                $source = new ArraySourceIterator($entries);
                $tmpName = tempnam(sys_get_temp_dir(), 'xlsx_');
                if(file_exists($tmpName)) unlink($tmpName);             // hacky way to just get a random name
                $writer = new XlsxWriter($tmpName);
                Handler::create($source, $writer)->export();
            }
            // $writer->close();
            $zip->addFile($tmpName, $costumer.DIRECTORY_SEPARATOR.$costumer.'_'.date('m.Y').'.xlsx');
        }
        if (!$zip->close()) throw new \RuntimeException(_('Cannot close ' . $zipName));
        $response = new BinaryFileResponse($zipName);
        $response->headers->set('Content-Type', 'application/zip');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'Teilnehmer_' . date('m.Y') . '.zip');
        $this->addFlash('sonata_flash_success', 'successfully exported');
        return $response;
        // return new StreamedResponse(function () use ($sources, $writer) {
            
        // }, 200, [
        //     'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        //     'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        // ]);
    }
}
