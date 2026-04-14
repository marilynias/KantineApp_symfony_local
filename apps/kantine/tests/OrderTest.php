<?php

namespace Kantine\Tests;

use DateTime;
use Doctrine\ORM\EntityManager;
use Kantine\Repository\OrderRepository;
use Shared\Entity\Costumer;
use Shared\Repository\CostumerRepository;
use Shared\Repository\SonataUserUserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

class OrderTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManager $entityManager;
    protected static function createKernel(array $options = []): KernelInterface
    {
        static::$class ??= static::getKernelClass();

        $env = $options['environment'] ?? $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'test';
        $debug = $options['debug'] ?? $_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? true;

        return new static::$class($env, $debug, 'kantine');
    }

    public function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->entityManager->beginTransaction();
        parent::setUp();
    }

    public function tearDown(): void
    {
        if( $this->entityManager->getConnection()->getTransactionNestingLevel()>0)
            $this->entityManager->rollback();
        // else
        //     var_dump("test");
        parent::tearDown();
    }

    /** I have no idea why $this->client->loginUser($adminUser) is not enough, but we have to manually submit the form from /login */
    private function authenticate(): void
    {
        $userRepository = $this->getContainer()->get(SonataUserUserRepository::class);
        $adminUser = $userRepository->findOneByUsername('admin');
        $this->client->loginUser($adminUser);

        $this->client->request('GET', '/login', );
        $this->assertResponseIsSuccessful('Could not get login page');
        $this->client->submitForm('login', [
            "_username"=>	"admin",
            "_password"=>	"admin"
        ]);
    }
    // protected function pre

    public function testLoadsKantinePage(): void
    {
        $this->authenticate();
        $crawler = $this->client->request('GET', '/');
        $this->assertResponseIsSuccessful('Could not get kantine main page');
        $this->assertCount(1, $crawler->filter('.menus'), ' Main page does not have menu buttons');
    }

    // private function 

    public function testPostValidOrder(): void
    {
        $container = static::getContainer();
        
        $this->authenticate();
        
        $this->client->request('GET', '/', );
        $this->assertResponseIsSuccessful('Could not get kantine main page');
        
        $costumerRepository = $container->get(CostumerRepository::class);
        // $newCostumer = $costumerRepository->getRandomCostumer();
        $count = $costumerRepository->countAll();
        $newCostumer = new Costumer();
        $newCostumer->active = True;
        $newCostumer->firstname ="test ".$count;
        $newCostumer->lastname="test ".$count;
        $this->entityManager->persist($newCostumer);
        $this->entityManager->flush();
        $this->assertNotNull($newCostumer, "could not get any costumers without orders");

        $this->client->submitForm('order_dto_save', [
            "order_dto[Costumer]" => $newCostumer->id,
            "order_dto[ordered_item]" => "4.5",
            "order_dto[tax]" => "7",
            // "order_dto[_token]" => "fhr8d5sha3a69tpv24s5"
        ]);
        $this->assertResponseIsSuccessful("create order not sucessfull: ". $this->client->getResponse());
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    public function testUpdateOrder(): void
    {
        $container = static::getContainer();

        $this->authenticate();

        $this->client->request('GET', '/', );
        $this->assertResponseIsSuccessful('Could not get kantine main page');

        $costumerRepository = $container->get(CostumerRepository::class);
        $orderRepository = $container->get(OrderRepository::class);
        $costumers = $costumerRepository->getAll();
        $count = 0;
        $new_ordered_item = "8.5";

        foreach ($costumers as $costumer) {
            $existing_order = $orderRepository->findCostumerOrderAtDate($costumer, new DateTime());
            if($existing_order){
                if ($existing_order->getOrderedItem()==$new_ordered_item) continue;
                $this->client->submitForm('order_dto_save', [
                    "order_dto[Costumer]" => $costumer->id,
                    "order_dto[ordered_item]" => $new_ordered_item,
                    "order_dto[tax]" => "7",
                    // "order_dto[_token]" => "fhr8d5sha3a69tpv24s5"
                ]);
                $this->assertResponseStatusCodeSame(Response::HTTP_ALREADY_REPORTED);
                // $this->assertResponseIsSuccessful(' Request not sucessfull. "already ordered" dialog should still have Responsecode 200');
                $this->assertSelectorExists('#order_dto_update', 'Response does not have "already ordered" dialog or update option');
                $this->assertFalse($existing_order->getOrderedItem()==$new_ordered_item);
                $this->client->submitForm('order_dto_update', [
                    "order_dto[Costumer]" => $costumer->id,
                    "order_dto[ordered_item]" => $new_ordered_item,
                    "order_dto[tax]" => "7",
                    // "order_dto[_token]" => "fhr8d5sha3a69tpv24s5"
                ]);
                $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
                $count++;
                break;
            }
        }
        $this->assertFalse($count=== 0, 'No valid costumers found?');
    }

    public function testTestAdminGet(): void
    {
        $this->authenticate();

        $this->client->request('GET', '/admin/dashboard', );
        $this->assertResponseIsSuccessful('Could not get admin main page: '.$this->client->getResponse()->getStatusCode());

        foreach (["shared/sonatauseruser", "shared/costumer", "kantine/order"] as $key) {
            $this->client->request('GET', '/admin/'.$key.'/list', );
            $this->assertResponseIsSuccessful('Could not get '.$key.' page: '.$this->client->getResponse()->getStatusCode());
            $crawler = $this->client->getCrawler();
            
            // view
            $entries = $crawler->filter('.sonata-link-identifier');
            if(count($entries) > 0){
                $this->client->click($entries->first()->link());
                $this->assertResponseIsSuccessful('Could get entity '.$key.' page: '.$this->client->getResponse()->getStatusCode());
                // var_dump($this->client->getResponse()->getContent());
            }

            // edit
            $entries = $crawler->filter('.edit_link');
            if(count($entries) > 0){
                $this->client->click($entries->first()->link());
                $this->assertResponseIsSuccessful('Could get entity '.$key.' page: '.$this->client->getResponse()->getStatusCode());
            }

            // create
            $entries = $crawler->filter('.sonata-action-element');
            if(count($entries) > 0){
                $this->client->click($entries->first()->link());
                $this->assertResponseIsSuccessful('Could get entity '.$key.' page: '.$this->client->getResponse()->getStatusCode());
            }
        }
    }
}


