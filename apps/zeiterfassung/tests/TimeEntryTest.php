<?php

namespace Zeiterfassung\Tests;

use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManager;
use Shared\Repository\CostumerRepository;
use Shared\Repository\SonataUserUserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Zeiterfassung\Repository\TimeEntryRepository;

class TimeEntryTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManager $entityManager;
    protected static function createKernel(array $options = []): KernelInterface
    {
        static::$class ??= static::getKernelClass();

        $env = $options['environment'] ?? $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'test';
        $debug = $options['debug'] ?? $_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? true;

        return new static::$class($env, $debug, 'zeiterfassung');
    }

    /** I have no idea why $this->client->loginUser($adminUser) is not enough, but we have to manually submit the form from /login */
    private function authenticate(): void
    {
        $userRepository = $this->getContainer()->get(SonataUserUserRepository::class);
        $adminUser = $userRepository->findOneByUsername('admin');
        $this->client->loginUser($adminUser);
        // 

        // $this->loginUser()
        $this->client->request('GET', '/admin/logout', );
        $this->client->request('GET', '/admin/login', );
        // var_dump($this->client->getResponse()->getContent());
        $this->assertResponseIsSuccessful('Could not get login page');
        $this->client->submitForm('submit', [
            "_username"=>	"admin",
            "_password"=>	"admin"
        ]);
        // var_dump($this->client->getResponse());
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
        parent::tearDown();
    }


    private function getToken(): string
    {
        $userRepository = static::getContainer()->get(SonataUserUserRepository::class);
        $testUser = $userRepository->findOneByUsername('admin');
        $this->client->loginUser($testUser, 'main');
        // for some reason the Request fails with invalid credentals if you are not logged in??
        $this->client->request('POST', '/api/login', content: json_encode(["username" => "admin", "password" => "admin"]));
        $content = $this->client->getResponse()->getContent();

        $this->assertResponseIsSuccessful('Could not get JWT Token: '.$content);

        $deserialized =  json_decode($content, true);
        $this->assertTrue(array_key_exists("token", $deserialized), "Response does not have token: ".$content);
        return $deserialized["token"];
    }

    private function postTimeentry(string $token, string $id): array
    {
        $this->client->request('POST', '/api', server:[
            "HTTP_AUTHORIZATION" => "Bearer ".$token
        ], content: json_encode(["barcode" => $id]));

        return json_decode($this->client->getResponse()->getContent(), true);
    }

    public function testPostValidTimeentry(): void
    {
        $token = $this->getToken($this->client);
        $costumerRepository = static::getContainer()->get(CostumerRepository::class);
        $timeEntryRepository = static::getContainer()->get(TimeEntryRepository::class);
        $costumer = $costumerRepository->getRandomCostumer();

        $existing = $timeEntryRepository->getTimeEntryForUser($costumer);
        if($existing){
            $this->entityManager->remove($existing);
            $this->entityManager->flush();
        }

        $content = $this->postTimeentry($token, $costumer->id);
        // var_dump($content);
        $this->assertResponseIsSuccessful('Could not post TimeEntry: '.$content);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED, "Wrong status code: ".$this->client->getResponse());


        // test cooldown
        $cooldown_response = $this->postTimeentry( $token, $costumer->id);
        $this->assertResponseStatusCodeSame(Response::HTTP_TOO_MANY_REQUESTS);

        // set timeentry to earlier to avoid cooldown
        $timeEntry = $timeEntryRepository->getTimeEntryForUser($costumer);
        $this->assertNotNull($timeEntry, "could not find created TimeEntry");
        $timeEntry->setCheckinTime(new DateTime()->setTime(0, 0, 0));

        $this->entityManager->persist($timeEntry);
        $this->entityManager->flush();

        $logout_response = $this->postTimeentry($token, $costumer->id);
        $this->assertResponseIsSuccessful('Could not checkout TimeEntry: '.$content);
    }

    public function testOptionalTimestamp(): void
    {
        $token = $this->getToken($this->client);
        $costumerRepository = static::getContainer()->get(CostumerRepository::class);
        $timeEntryRepository = static::getContainer()->get(TimeEntryRepository::class);
        $costumer = $costumerRepository->getRandomCostumer();
        $existing = $timeEntryRepository->getTimeEntryForUser($costumer);
        if($existing){
            $this->entityManager->remove($existing);
            $this->entityManager->flush();
        }

        // always 10 mins previously
        $time_to_post = new DateTime()->sub( DateInterval::createFromDateString("10 minutes"))->format("h:m:s");
        // var_dump($time_to_post);

        $this->client->request('POST', '/api', server:[
            "HTTP_AUTHORIZATION" => "Bearer ".$token
        ], content: json_encode(["barcode" => $costumer->id, "time" => $time_to_post]));

        $res = json_decode($this->client->getResponse()->getContent(), true);
        // var_dump($res);
        $this->assertTrue($time_to_post === $res["time"], "posted time ".$time_to_post." is not the same as saved time ".$res["time"] ?? $this->client->getResponse()->getContent());
    }

    public function testTestAdminGet(): void
    {
        $this->authenticate();

        $this->client->request('GET', '/admin/dashboard', );
        $this->assertResponseIsSuccessful('Could not get admin main page: '.$this->client->getResponse()->getStatusCode());

        foreach (["shared/sonatauseruser", "shared/costumer", "attendance", "zeiterfassung/fauser"] as $key) {
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


