<?php

namespace Shared\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Shared\Entity\Costumer;

class CostumerFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        for ($i=0; $i < 10; $i++) { 
            $costumer = new Costumer()
                ->setDepartment(array_rand(Costumer::DEPARTMENTS));
            $costumer->active = true;
            $costumer->firstname = 'F'.$i;
            $costumer->lastname = 'L'.$i;
            $manager->persist($costumer);
        }

        $manager->flush();
    }
}
