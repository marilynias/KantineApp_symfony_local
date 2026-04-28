<?php

namespace Shared\Controller;

use Shared\Entity\Costumer;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\ConstraintViolationListInterface;

final class CostumerController extends AbstractFOSRestController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator
    ) {}

    private function flashCostumerAddError(Costumer $costumer, string $error)
    {
        $this->addFlash('error', new TranslatableMessage(
            "%firstname% %lastname% in %dep% could not me added: %cause%",
            [
                '%cause%' => $error,
                '%firstname%' => $costumer->firstname,
                '%lastname%' => $costumer->lastname,
                '%dep%' => $costumer->getDepartment() ?? _("NO DEPARTMENT SET"),
            ]
        ));
    }

    #[IsGranted('ROLE_ADMIN_COSTUMER_VIEW')]
    #[Route('/api/costumers/{id}', name: 'get_costumer')]
    public function getCostumer(Request $request, $id): Response | JsonResponse
    {
        // get Costumer in JSON compatible format
        $data = $this->entityManager->getRepository(Costumer::class)->findByCode($id)->getArrayResult();
        if (!$data) throw $this->createNotFoundException(
            'No product found for id ' . $id
        );

        if ($request->getRequestFormat() == 'html') return $this->json($data);
        $view = $this->view($data);
        return $this->handleView($view);
    }

    #[IsGranted('ROLE_ADMIN_COSTUMER_VIEW')]
    #[Route('/api/costumers', name: 'get_costumers',)]
    public function getCostumerFiltered(Request $request): Response | JsonResponse
    {
        // get Costumer in JSON compatible format
        $filter = $request->query->all();
        $data = $this->entityManager->getRepository(Costumer::class)->filterBy($filter)->getArrayResult();

        if ($request->getRequestFormat() == 'html') return $this->json($data);
        $view = $this->view($data);
        return $this->handleView($view);
    }

    #[Route('/api/allowed_departments', name: 'get_allowed_departments')]
    public function getAllowedDepartments(Request $request): Response
    {
        $data = Costumer::DEPARTMENTS;
        if ($request->getRequestFormat() == 'html') return $this->json($data);
        $view = $this->view($data);
        return $this->handleView($view);
    }


    private function handle_upload_errors(Costumer $costumer, ConstraintViolationListInterface $errors): bool{
        foreach ($errors as $key => $error) {
            // Costumer already exists
            if ($error->getConstraint() instanceof UniqueEntity && $costumer->getDepartment()) {
                $cause = $error->getCause();
                if (count($cause) != 1) {
                    $this->flashCostumerAddError($costumer, $error->getMessage());
                    return false;
                }
                $existing = $cause[0];

                // nothing to update
                if($existing->getDepartment() == $costumer->getDepartment()){
                    $this->flashCostumerAddError($costumer, "Already exists.");
                    return True;
                }

                // save existing costumer with new department
                $existing->setDepartment($costumer->getDepartment());
                $err_new = $this->validator->validate($existing);
                if ($err_new->count() > 0) {
                    $this->flashCostumerAddError($costumer, $err_new[0]->getMessage());
                    return false;
                }
                $this->entityManager->persist($existing);
                $this->entityManager->flush();
                $this->addFlash('notice', new TranslatableMessage(
                    'updated department %dep% for existing costumer: %firstname% %lastname% &emsp; <img src="/%barcode%"> ',
                    [
                        '%firstname%' => $existing->getFirstname(),
                        '%lastname%' => $existing->getLastname(),
                        '%dep%' => $existing->getDepartment() ?? _("NO DEPARTMENT SET"),
                        '%barcode%' => $existing->getBarcode()
                    ]
                ));
                return true;
            } else {
                $this->flashCostumerAddError($costumer, $error->getMessage());
                return false;
            }
        }
        return false;
    }

    #[IsGranted('ROLE_ADMIN_COSTUMER_CREATE')]
    #[Route('/add_users', name: 'upload_users')]
    public function uploadUsers(Request $request): Response
    {

        $form = $this->createFormBuilder()
            ->add('file', FileType::class)
            ->add('send', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fileField = $form["file"]->getData();

            if ($fileField->getMimeType() != "text/csv" && $fileField->getMimeType() != "text/plain") {
                $this->addFlash('error', message: _("File must be csv"));
                return $this->render('components/Form.html.twig', [
                    'form' => $form,
                ]);
            }

            //rows
            $lines = str_getcsv($fileField->getContent(), "\n", '"', "\\");
            $deliminator = str_contains($lines[0], ";") ? ";" : ",";
            foreach ($lines as $line) {
                if (!str_contains($line, $deliminator)) break;
                $data = str_getcsv($line, $deliminator, '"', "\\");
                if (sizeof($data) < 2) break;
                try {
                    $costumer = new Costumer();
                    $costumer
                        ->setDepartment(Department: count($data) >= 3 ? $data[2] : null);
                    $costumer->active = true;
                    $costumer->firstname = $data[0];
                    $costumer->lastname= $data[1];

                    $errors = $this->validator->validate($costumer);
                    if ($errors->count()) {
                        if(!$this->handle_upload_errors($costumer, $errors)) break;
                    } else {
                        // actually executes the queries (i.e. the INSERT query)
                        $this->entityManager->persist($costumer);
                        $this->entityManager->flush();
                        $this->addFlash('notice', new TranslatableMessage(
                            'sucessfully added: %firstname% %lastname% in %dep% &emsp; <img src="/%barcode%"> ',
                            [
                                '%firstname%' => $costumer->firstname,
                                '%lastname%' => $costumer->lastname,
                                '%dep%' => $costumer->getDepartment() ?? _("NO DEPARTMENT SET"),
                                '%barcode%' => $costumer->getBarcode()
                                ]
                            ));
                        }
                    } catch (\Throwable $th) {
                        $this->flashCostumerAddError($costumer, (string)$th);
                    }
                }
                
            }

        return $this->render('components/Form.html.twig', [
            'form' => $form,
        ]);
    }

    /* 
        For testing.
        data from: 
            https://nachnamen.net/deutschland
            https://opendata.jena.de/dataset/vornamen
    */
    #[When(env: 'dev')]
    #[Route('/generate/costumer/{num}', name: 'gen_costumers')]
    public function genUsers(Request $request, $num): Response
    {
        $dir = 'uploads';
        // look in public/barcodes/${id}.svg
        if (!is_dir($dir)) {
            mkdir($dir, 0755);
        }

        $names = [];
        foreach (["vornamen2024_opendata_datenschutz.csv", "nachnamen.csv"] as $key => $value) {
            $sur_loc = join(DIRECTORY_SEPARATOR, [$dir, $value]);
            $file = fopen($sur_loc, "r");
            $content = trim(fread($file, filesize($sur_loc)));
            // [0=>[Vornamen], 1=>[Nachnamen]]
            $names[$key] = explode("\n", $content);
            fclose($file);
        }

        $generated = [];
        for ($i = 0; $i < $num; $i++) {
            $costumer = new Costumer();
            $costumer->firstname = $names[0][array_rand($names[0])];
            $costumer->lastname = $names[1][array_rand($names[1])];
            $costumer->active = true;
            $errors = $this->validator->validate($costumer);
            if ($errors->count() > 0) {
                continue;
                return new Response((string)$errors);
            }
            $this->entityManager->persist($costumer);
            $this->entityManager->flush();
            $generated[$i] = join(" ", [$costumer->id, $costumer->firstname, $costumer->lastname]) . "<br>";
        }

        return new Response(implode($generated));
    }

    #[IsGranted('ROLE_ADMIN_COSTUMER_DELETE')]
    #[Route('/cron/delete_old_costumers', name: 'del_costumers')]
    public function deleteOldInactiveCostumers(Request $request): Response
    {
        $repository = $this->entityManager->getRepository(Costumer::class);
        $count = $repository->deleteOldInactive();
        return new Response($count ? sprintf('Deleted %d old Costumer(s).', $count) : 'No Costumers to delete');
    }
}
