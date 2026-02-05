<?php

namespace App\Twig\Components;

//use App\Entity\Newsletter;
//use App\Entity\Subscriber;
//use App\Entity\Subscription;
//use Doctrine\ORM\EntityManagerInterface;
//use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
//use Symfony\Component\Mailer\MailerInterface;
//use Symfony\Component\Mime\Email;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use App\Service\Newsletter\NewsletterSubscriptionManager;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

#[AsLiveComponent]
final class NewsletterSubscription
{
    use DefaultActionTrait;

    public function __construct(
        protected NewsletterSubscriptionManager $manager
    ) {}


    #[LiveProp(writable: true)]
    public string $email = '';

    #[LiveProp(writable: true)]
    public ?string $birthDate = null;

    #[LiveProp(writable: true)]
    public array $newsletterIds = [];

    #[LiveProp]
    public bool $success = false;

    #[LiveProp]
    public ?string $error = null;

    #[LiveProp]
    public ?string $info = null;

    #[LiveProp]
    public array $subscribedNewsletterNames = [];

    #[LiveAction]
    public function submit(): void
    {
        $this->success = false;
        $this->error = null;
        $this->info = null;
        $this->subscribedNewsletterNames = [];

        if (strlen($this->email) > 180) {
            $this->error = 'Email trop long.';
            return;
        }

        if (!$this->birthDate) {
            $this->error = 'Date de naissance obligatoire.';
            return;
        }

        $result = $this->manager->subscribe(
            email: $this->email,
            birthDateRaw: $this->birthDate,
            newsletterIds: $this->newsletterIds,
        );

        $this->error = $result->error;
        $this->info = $result->info;
        $this->subscribedNewsletterNames = $result->newsletterNames;
        $this->success = $result->success;

        if ($result->error) {
            return;
        }

        // reset form si OK
        $this->email = '';
        $this->birthDate = null;
        $this->newsletterIds = [];
    }

    /**
     * @return array
     */
    public function getNewsletters(): array
    {
        return $this->manager->getNewsletters();
    }

    /**
     * @return void
     */
    #[LiveAction]
    public function validateBirthDate(): void
    {
        $this->error = null;
        $this->info = null;

        if (!$this->birthDate) {
            return;
        }

        try {
            $birthDate = new \DateTimeImmutable($this->birthDate);
        } catch (\Throwable) {
            $this->error = 'Date de naissance invalide.';
            return;
        }

        $today = new \DateTimeImmutable('today');
        $age = $birthDate->diff($today)->y;

        if ($age < 16) {
            $this->info = 'Moins de 16 ans : la demande sera enregistrée mais non activée.';
        }
    }

}
