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
        $format->setPattern("EEEE dd.M.y");

        $selectedUsers = $query->execute();
        $data = [];
        $data1 = [];

        // sort entries into [Costumer][Month][entiry]
        foreach ($selectedUsers as $timeEntry) {
            if(!$timeEntry instanceof TimeEntry) continue;
            $month = $timeEntry->getCheckinTime()->format('MM.yyyy');
            $data[$timeEntry->getUser()->getFullname()][$month][] = [
                'Datum'=>   datefmt_format($format, $timeEntry->getCheckinTime()), 
                'Eintrag' => $timeEntry->getCheckinTime()->format('h:m'), 
                'Austrag' => $timeEntry->getCheckoutTime()? $timeEntry->getCheckoutTime()->format('h:m'):''];
        }

        $zipName = tempnam(sys_get_temp_dir(), 'zip_');
        $zip = new ZipArchive();
        if ($zip->open($zipName, ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException(_('Cannot open ' . $zipName));
        }

        // write into zip archive structured costumer/month.xlsx
        foreach ($data as $costumer => $months) {
            $zip->addEmptyDir($costumer);
            foreach ($months as $entries) {
                $source = new ArraySourceIterator($entries);
                $tmpName = tempnam(sys_get_temp_dir(), 'xlsx_');
                if(file_exists($tmpName)) unlink($tmpName);             // hacky way to just get a random name, not the new file
                $writer = new XlsxWriter($tmpName);
                Handler::create($source, $writer)->export();
                $zip->addFile($tmpName, $costumer.DIRECTORY_SEPARATOR.$costumer.'_'.date('m.Y').'.xlsx');
            }
        }
        if (!$zip->close()) throw new \RuntimeException(_('Cannot close ' . $zipName));


        $response = new BinaryFileResponse($zipName);
        $response->headers->set('Content-Type', 'application/zip');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'Teilnehmer_' . date('m.Y') . '.zip');
        $this->addFlash('sonata_flash_success', 'successfully exported');
        return $response;
    }
}
