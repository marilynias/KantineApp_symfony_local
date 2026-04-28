<?php

namespace Zeiterfassung\Controller;

use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\Exporter\Handler;
use Sonata\Exporter\Source\ArraySourceIterator;
use Sonata\Exporter\Writer\XlsxWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Zeiterfassung\Entity\TimeEntry;
use ZipArchive;

final class TimeEntryBatchController extends AbstractController
{
    public function batchGetReportAction(ProxyQueryInterface $query, AdminInterface $admin): BinaryFileResponse|RedirectResponse
    {
        $admin->checkAccess('list');

        $format = datefmt_create('de-DE');
        $format->setPattern("EEEE dd.MM.y");

        $selectedUsers = $query->execute();
        $data = [];
        $data1 = [];

        // sort entries into [Costumer][Month][entiry]
        foreach ($selectedUsers as $timeEntry) {
            if(!$timeEntry instanceof TimeEntry) continue;
            $month = $timeEntry->getCheckinTime()->format('m.y');
            $data[$timeEntry->getUser()->getFullname()][$month][] = [
                'Datum'=>   datefmt_format($format, $timeEntry->getCheckinTime()), 
                'Eintrag' => $timeEntry->getCheckinTime()->format('H:i'), 
                'Austrag' => $timeEntry->getCheckoutTime()? $timeEntry->getCheckoutTime()->format('H:i'):''];
        }

        $zipName = tempnam(sys_get_temp_dir(), 'zip_');
        $zip = new ZipArchive();
        if ($zip->open($zipName, ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException(_('Cannot open ' . $zipName));
        }

        // write into zip archive structured costumer/month.xlsx
        foreach ($data as $costumer => $months) {
            $zip->addEmptyDir($costumer);
            foreach ($months as $month => $entries) {
                $source = new ArraySourceIterator($entries);
                $tmpName = tempnam(sys_get_temp_dir(), 'xlsx_');
                if(file_exists($tmpName)) unlink($tmpName);             // hacky way to just get a random name, not the new file
                $writer = new XlsxWriter($tmpName);
                Handler::create($source, $writer)->export();
                $zip->addFile($tmpName, $costumer.DIRECTORY_SEPARATOR.$costumer.'_'.$month.'.xlsx');
            }
        }
        if (!$zip->close()) throw new \RuntimeException(_('Cannot close ' . $zipName));


        $response = new BinaryFileResponse($zipName);
        $response->headers->set('Content-Type', 'application/zip');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'Teilnehmer_Zeiteinträge' . '.zip');
        $this->addFlash('sonata_flash_success', 'successfully exported');
        return $response;
    }
}
