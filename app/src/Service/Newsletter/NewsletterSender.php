<?php

namespace App\Service\Newsletter;

use App\Entity\Newsletter;
use App\Entity\Subscription;
use App\Interface\NewsletterSenderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class NewsletterSender implements NewsletterSenderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MailerInterface $mailer,
    ) {}

    /**
     * @param Newsletter $newsletter
     * @param bool $dryRun
     * @param int|null $limit
     * @param callable|null $onRecipient
     * @return SendResult
     */
    public function sendNewsletter(
        Newsletter $newsletter,
        bool $dryRun = false,
        ?int $limit = null,
        ?callable $onRecipient = null,
    ): SendResult {
        $result = new SendResult();

        $qb = $this->em->createQueryBuilder()
            ->select('sub', 's')
            ->from(Subscription::class, 'sub')
            ->join('sub.subscriber', 's')
            ->where('sub.newsletter = :nl')
            ->andWhere('sub.isActive = :active')
            ->setParameter('nl', $newsletter)
            ->setParameter('active', true);

        $iterable = $qb->getQuery()->toIterable();

        foreach ($iterable as $subscription) {
            /** @var Subscription $subscription */
            $result->processed++;

            $to = (string) $subscription->getSubscriber()->getEmail();
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                $result->skippedInvalidEmail++;
                $onRecipient && $onRecipient($to, false, null);
                continue;
            }

            try {
                if (!$dryRun) {
                    $this->mailer->send($this->buildEmail($newsletter, $to));
                    $result->sent++;
                } else {
                    $result->sent++;
                }

                $onRecipient && $onRecipient($to, true, null);
            } catch (\Throwable $e) {
                $result->errors++;
                $onRecipient && $onRecipient($to, false, $e);
            }

            if ($limit !== null && $result->sent >= $limit) {
                break;
            }
        }

        return $result;
    }

    /**
     * @param Newsletter $newsletter
     * @param string $to
     * @return Email
     */
    private function buildEmail(Newsletter $newsletter, string $to): Email
    {
        $subject = sprintf('Newsletter %s', $newsletter->getName());
        $text = sprintf(
            "Bonjour,\n\nVoici la newsletter \"%s\".\n\nBonne journée !",
            $newsletter->getName()
        );

        return (new Email())
            ->from('no-reply@annexx.test')
            ->to($to)
            ->subject($subject)
            ->text($text);
    }

    /**
     * @param int $newsletterId
     * @param bool $dryRun
     * @param int|null $limit
     * @param callable|null $onRecipient
     * @return SendResult
     */
    public function sendById(int $newsletterId, bool $dryRun = false, ?int $limit = null, ?callable $onRecipient = null): SendResult
    {
        $newsletter = $this->em->getRepository(Newsletter::class)->find($newsletterId);
        if (!$newsletter) {
            throw new \RuntimeException("Newsletter #$newsletterId introuvable.");
        }

        return $this->sendNewsletter($newsletter, $dryRun, $limit, $onRecipient);
    }

}
