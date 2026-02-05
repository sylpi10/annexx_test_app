<?php

namespace App\DataFixtures;

use App\Entity\Subscription;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use App\Entity\Subscriber;
use App\Entity\Newsletter;

class SubscriptionFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $newsletterNameByKey = [
            'tech' => 'Tech',
            'marketing' => 'Marketing',
            'rh' => 'RH',
        ];

        $data = [
            // actifs
            ['john@lennon.com', 'tech'],
            ['ringo@starr.com', 'marketing'],
            ['john@lennon.com', 'rh'],

            // inactifs, moins de 16 ans
            ['georges@harrisson.com', 'tech'],
            ['paul@mccartney.com', 'marketing'],
        ];

        $subscriberRepo = $manager->getRepository(Subscriber::class);
        $newsletterRepo = $manager->getRepository(Newsletter::class);

        foreach ($data as [$email, $nlKey]) {
            /** @var Subscriber|null $subscriber */
            $subscriber = $subscriberRepo->findOneBy(['email' => $email]);
            if (!$subscriber) {
                continue;
            }

            $nlName = $newsletterNameByKey[$nlKey] ?? null;
            if (!$nlName) {
                continue;
            }

            /** @var Newsletter|null $newsletter */
            $newsletter = $newsletterRepo->findOneBy(['name' => $nlName]);
            if (!$newsletter) {
                continue;
            }

            $age = $subscriber->getBirthDate()->diff(new \DateTimeImmutable('today'))->y;

            $sub = new Subscription();
            $sub->setSubscriber($subscriber);
            $sub->setNewsletter($newsletter);
            $sub->setIsActive($age >= 16);

            $manager->persist($sub);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            NewsletterFixtures::class,
            SubscriberFixtures::class,
        ];
    }
}
