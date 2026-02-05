<?php

namespace App\Twig\Components;

use App\Entity\Newsletter;
use App\Entity\Subscriber;
use App\Entity\Subscription;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsLiveComponent]
final class NewsletterSubscription
{
    use DefaultActionTrait;

    public function __construct(protected EntityManagerInterface $em, protected MailerInterface $mailer,) {}

    #[LiveProp(writable: true)]
    public string $email = '';

    #[LiveProp(writable: true)]
    public ?string $birthDate = null;

    #[LiveProp(writable: true)]
    public array $newsletterIds = [];

    #[LiveProp]
    public ?string $error = null;

    #[LiveProp]
    public ?string $info = null;

    #[LiveProp]
    public bool $success = false;

    #[LiveProp]
    public array $subscribedNewsletterNames = [];

    #[LiveAction]
    public function validateBirthDate(): void
    {
        $this->error = null;
        $this->info = null;

        if (!$this->birthDate) {
            return;
        }

        $birthDate = $this->parseAndValidateBirthDate($this->birthDate);
        if (!$birthDate) {
            return;
        }

        $today = new \DateTimeImmutable('today');
        $age = $birthDate->diff($today)->y;

        if ($age < 16) {
            $this->info = 'Moins de 16 ans : la demande sera enregistrée mais non activée.';
        }
    }

    #[LiveAction]
    /**
     * TODO : refacto
     */
    public function submit(): void
    {
        $this->success = false;
        $this->error = null;
        $this->info = null;
        $this->subscribedNewsletterNames = [];

        $email = trim($this->email);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error = 'Email invalide.';
            return;
        }

        if (!$this->birthDate) {
            $this->error = 'Date de naissance obligatoire.';
            return;
        }

        $birthDate = $this->parseAndValidateBirthDate($this->birthDate);
        if (!$birthDate) {
            return;
        }

        if (count($this->newsletterIds) === 0) {
            $this->error = 'Choisissez au moins une newsletter.';
            return;
        }

        $today = new \DateTimeImmutable('today');
        $age = $birthDate->diff($today)->y;
        $isActive = $age >= 16;

        $subscriberRepo = $this->em->getRepository(Subscriber::class);
        /** @var Subscriber|null $subscriber */
        $subscriber = $subscriberRepo->findOneBy(['email' => $email]);

        if (!$subscriber) {
            $subscriber = new Subscriber();
            $subscriber->setEmail($email);
            $this->em->persist($subscriber);
        }

        $subscriber->setBirthDate($birthDate);

        $newsletterRepo = $this->em->getRepository(Newsletter::class);
        $subscriptionRepo = $this->em->getRepository(Subscription::class);

        foreach ($this->newsletterIds as $id) {
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
            $this->subscribedNewsletterNames[] = $newsletter->getName();
        }

        $this->em->flush();

        $subject = $isActive
            ? 'Confirmation d’inscription aux newsletters'
            : 'Inscription non validée (moins de 16 ans)';

        $body = $isActive
            ? "Bonjour,\n\nVotre inscription est confirmée pour :\n- " . implode("\n- ", $this->subscribedNewsletterNames) . "\n\nMerci !"
            : "Bonjour,\n\nNous avons bien enregistré votre demande pour :\n- " . implode("\n- ", $this->subscribedNewsletterNames) . "\n\nCependant, l’abonnement n’est pas activé car vous avez moins de 16 ans.\n\nMerci !";

        $this->mailer->send(
            (new Email())
                ->from('no-reply@annexx.test')
                ->to($email)
                ->subject($subject)
                ->text($body)
        );


        if ($isActive) {
            $this->success = true;
        } else {
            $this->info = 'Demande enregistrée, mais abonnement non activé (moins de 16 ans).';
        }

        $this->email = '';
        $this->birthDate = null;
        $this->newsletterIds = [];
    }

    public function getNewsletters(): array
    {
        return $this->em->getRepository(Newsletter::class)->findAll();
    }

    /**
     * TODO refacto
     * premières validations ici mais voir sur entity pour faire des Assertions
     */
    private function parseAndValidateBirthDate(string $raw): ?\DateTimeImmutable
    {
        try {
            $birthDate = new \DateTimeImmutable($raw);
        } catch (\Exception) {
            $this->error = 'Date de naissance invalide.';
            return null;
        }

        $today = new \DateTimeImmutable('today');

        if ($birthDate > $today) {
            $this->error = 'La date de naissance ne peut pas être dans le futur.';
            return null;
        }

        $age = $birthDate->diff($today)->y;
        if ($age > 120) {
            $this->error = 'Date de naissance invalide.';
            return null;
        }

        return $birthDate;
    }
}
