<?php

namespace App\Service\Newsletter;

use App\Entity\Newsletter;
use App\Entity\Subscriber;
use App\Entity\Subscription;
use App\Interface\NewsletterSubscriptionInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class NewsletterSubscriptionManager implements NewsletterSubscriptionInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MailerInterface $mailer,
    ) {}

    /**
     * @return array
     */
    public function getNewsletters(): array
    {
        return $this->em->getRepository(Newsletter::class)->findAll();
    }

    /**
     * @param string $email
     * @param string $birthDateRaw
     * @param array $newsletterIds
     * @return SubscribeResult
     * @throws TransportExceptionInterface
     */
    public function subscribe(string $email, string $birthDateRaw, array $newsletterIds): SubscribeResult
    {
        $email = trim($email);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new SubscribeResult(false, false, error: 'Email invalide.');
        }

        if ($birthDateRaw === '') {
            return new SubscribeResult(false, false, error: 'Date de naissance obligatoire.');
        }

        $birthDate = $this->parseAndValidateBirthDate($birthDateRaw);
        if (!$birthDate) {
            return new SubscribeResult(false, false, error: 'Date de naissance invalide.');
        }

        if (\count($newsletterIds) === 0) {
            return new SubscribeResult(false, false, error: 'Choisissez au moins une newsletter.');
        }

        $today = new \DateTimeImmutable('today');
        $age = $birthDate->diff($today)->y;
        $isActive = $age >= 16;

        // Subscriber
        $subscriberRepo = $this->em->getRepository(Subscriber::class);
        /** @var Subscriber|null $subscriber */
        $subscriber = $subscriberRepo->findOneBy(['email' => $email]);

        if (!$subscriber) {
            $subscriber = new Subscriber();
            $subscriber->setEmail($email);
            $this->em->persist($subscriber);
        }

        $subscriber->setBirthDate($birthDate);

        // Subscriptions
        $newsletterRepo = $this->em->getRepository(Newsletter::class);
        $subscriptionRepo = $this->em->getRepository(Subscription::class);

        $names = [];

        foreach ($newsletterIds as $id) {
            $newsletter = $newsletterRepo->find((int) $id);
            if (!$newsletter) {
                continue;
            }

            /** @var Subscription|null $sub */
            $sub = $subscriptionRepo->findOneBy([
                'subscriber' => $subscriber,
                'newsletter' => $newsletter,
            ]);

            if (!$sub) {
                $sub = new Subscription();
                $sub->setSubscriber($subscriber);
                $sub->setNewsletter($newsletter);
                $this->em->persist($sub);
            }

            $sub->setIsActive($isActive);
            $names[] = $newsletter->getName();
        }

        $this->em->flush();

        // Mail de confirmation (tu pourras passer en TemplatedEmail après)
        $this->sendConfirmationEmail($email, $names, $isActive);

        $info = $isActive
            ? null
            : 'Demande enregistrée, mais abonnement non activé (moins de 16 ans).';

        return new SubscribeResult(
            success: $isActive,        // si tu veux "success=true" même <16, mets true ici
            isActive: $isActive,
            newsletterNames: $names,
            info: $info
        );
    }

    /**
     * @param string $email
     * @param array $newsletterNames
     * @param bool $isActive
     * @return void
     * @throws TransportExceptionInterface
     */
    private function sendConfirmationEmail(string $email, array $newsletterNames, bool $isActive): void
    {
        $subject = $isActive
            ? 'Confirmation d’inscription aux newsletters'
            : 'Inscription non validée (moins de 16 ans)';

        $body = $isActive
            ? "Bonjour,\n\nVotre inscription est confirmée pour :\n- " . implode("\n- ", $newsletterNames) . "\n\nMerci !"
            : "Bonjour,\n\nNous avons bien enregistré votre demande pour :\n- " . implode("\n- ", $newsletterNames) . "\n\nCependant, l’abonnement n’est pas activé car vous avez moins de 16 ans.\n\nMerci !";

        $this->mailer->send(
            (new Email())
                ->from('no-reply@annexx.test')
                ->to($email)
                ->subject($subject)
                ->text($body)
        );
    }

    /**
     * @param string $raw
     * @return \DateTimeImmutable|null
     */
    private function parseAndValidateBirthDate(string $raw): ?\DateTimeImmutable
    {
        try {
            $birthDate = new \DateTimeImmutable($raw);
        } catch (\Throwable) {
            return null;
        }

        $today = new \DateTimeImmutable('today');

        if ($birthDate > $today) {
            return null;
        }

        $age = $birthDate->diff($today)->y;
        if ($age > 120) {
            return null;
        }

        return $birthDate;
    }

}
