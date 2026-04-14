<?php

namespace Zeiterfassung\Controller;

use Zeiterfassung\Entity\TimeEntry;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Shared\Entity\Costumer;
use Shared\Entity\SonataUserUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Polyfill\Intl\Icu\Exception\NotImplementedException;

use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

// #[Route(path: '/zeiterfassung', name: 'zeiterfassung')]
class ScannerPageController extends AbstractController
{
    
    static $cooldown = '+1 minutes';
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {}

    #[Route('/', name: 'main')]
    public function main(){
        return $this->redirectToRoute('sonata_admin_dashboard');
    }

    #[Route('/api', name: 'scanner_api', methods: ['POST'])]
    public function scannerApi(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['error' => 'No data provided'], 400);
        }

        // ------------- 2) Barcode scanning (legacy) -------------
        if (!isset($data['barcode'])) {
            return $this->json(['error' => 'No barcode provided'], 400);
        }

        $userEntity = $this->em->getRepository(Costumer::class)->find($data['barcode']);
        if (!$userEntity) {
            return $this->json(['error' => 'User with id '. $data['barcode'] .' not found'], 404);
        }
        
        assert( $userEntity instanceof Costumer);
        if (!$userEntity->active)
            return $this->json(['error' => 'User with id '. $data['barcode'] .'('.$userEntity->getFullName().') is not active'], 403);
        
        $lastEntry = $this->em->getRepository(TimeEntry::class)->getTimeEntryForUser($userEntity);
        $entry_time = new \DateTime($data['time']??"now");
        $todayStart = (clone $entry_time)->setTime(0, 0, 0);
        $todayEnd = (clone $entry_time)->setTime(23, 59, 59);
        if ($lastEntry) {
            // 1) If last entry has no checkout, update it (normal checkout)
            if ($lastEntry->getCheckoutTime() === null) {
                $cooldownEnd = (clone $lastEntry->getCheckinTime())->modify($this::$cooldown);
                if ($entry_time < $cooldownEnd) {
                    $remaining = $cooldownEnd->getTimestamp() - $entry_time->getTimestamp();
                    return new JsonResponse([
                        'error' => 'Cooldown active',
                        'minutes_remaining' => ceil($remaining / 60)
                    ], 429);
                }

                $lastEntry->setCheckoutTime($entry_time);
                $this->em->flush();

                return new JsonResponse([
                    'status' => 'checkout_update',
                    'user_ID' => $userEntity->id,
                    'time' => $entry_time->format('H:i:s')
                ], 200);
                
            } else {
                // 2) Last entry already has checkin + checkout → just update checkout
                $lastEntry->setCheckoutTime($entry_time);
                $this->em->flush();
            }

            return new JsonResponse([
                'status' => 'checkout_update_existing',
                'user_ID' => $userEntity->id,
                'time' => $entry_time->format('H:i:s')
            ], 200);
        } else {

            // 3) No entry today → create new checkin
            $newEntry = new TimeEntry();
            $newEntry->setUser($userEntity);
            $newEntry->setCheckinTime($entry_time);
            $newEntry->setCheckoutTime(null);
            $this->em->persist($newEntry);
            $this->em->flush();

            return new JsonResponse([
                'status' => 'checkin',
                'user' => $userEntity->getFullName(),
                'time' => $entry_time->format('H:i:s'),
                'cooldown_until' => (clone $entry_time)->modify($this::$cooldown)->format('H:i:s')
            ], 201);
        }
        
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function apiLogin(#[CurrentUser] ?SonataUserUser $editor, Request $request, JWTTokenManagerInterface $JWTManager): Response
    {
        if ($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] != "test") throw new \LogicException("this should be handled by JWT");
        $token = $JWTManager->create($editor);
        return $this->json(["token" => $token]);
    }

}