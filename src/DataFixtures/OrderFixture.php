<?php

namespace Shared\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Kantine\Entity\Order;
use Shared\DataFixtures\CostumerFixture;
use Shared\Repository\CostumerRepository;

class OrderFixture extends Fixture  implements DependentFixtureInterface
{
    public function __construct(private CostumerRepository $costumerRepository)
    {}

    public function load(ObjectManager $manager): void
    {
        // $costumers =  $this->costumerRepository->getAll();

        // // ensure atleast one costumer does not have an Order
        // array_pop($costumers);
        // shuffle($costumers);
        // foreach ($costumers as $costumer) {
        //     $order = new Order()
        //         ->setOrderedItem(rand(10, 100)/10)
        //         ->setCostumer($costumer)
        //         ->setTax(7);
        //     $manager->persist($order);
        // }

        // $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CostumerFixture::class,
        ];
    }
}
