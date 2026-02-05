<?php

namespace App\DataFixtures;

use App\Entity\Newsletter;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class NewsletterFixtures extends Fixture
{

    public function load(ObjectManager $manager): void
    {
        foreach (['Tech', 'Marketing', 'RH'] as $name) {
            $n = new Newsletter();
            $n->setName($name);
            $manager->persist($n);
        }

        $manager->flush();
    }
}
