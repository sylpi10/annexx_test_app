<?php

namespace App\DataFixtures;

use App\Entity\Subscriber;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SubscriberFixtures extends Fixture
{

    public function load(ObjectManager $manager): void
    {
        $subscribers = [
            [ 'john@lennon.com',  '1990-01-01'],
            ['ringo@starr.com', '1985-06-15'],
            ['georges@harrisson.com', '2010-03-10'], // < 16
            [ 'paul@mccartney.com', '2011-09-22'], // < 16
        ];

        foreach ($subscribers as [$email, $birth]) {
            $s = new Subscriber();
            $s->setEmail($email);
            $s->setBirthDate(new \DateTimeImmutable($birth));
            $manager->persist($s);
        }

        $manager->flush();
    }
}
