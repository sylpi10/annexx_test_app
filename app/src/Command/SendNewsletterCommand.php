<?php

namespace App\Command;

use App\Entity\Newsletter;
use App\Entity\Subscription;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:send-newsletter',
    description: 'Envoie une newsletter à tous les abonnés actifs (Subscription.isActive=true) pour une newsletter donnée.'
)]
class SendNewsletterCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MailerInterface $mailer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('newsletterId', InputArgument::REQUIRED, 'ID de la newsletter à envoyer')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'N’envoie pas d’email, affiche juste les destinataires')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limite le nombre d’emails envoyés', null);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws TransportExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $newsletterId = (int) $input->getArgument('newsletterId');
        $dryRun = (bool) $input->getOption('dry-run');
        $limitOpt = $input->getOption('limit');
        $limit = $limitOpt !== null ? max(1, (int) $limitOpt) : null;

        /** @var Newsletter|null $newsletter */
        $newsletter = $this->em->getRepository(Newsletter::class)->find($newsletterId);
        if (!$newsletter) {
            $io->error(sprintf('Newsletter #%d introuvable.', $newsletterId));
            return Command::FAILURE;
        }

        $io->title(sprintf('Envoi newsletter #%d: %s', $newsletter->getId(), $newsletter->getName()));

        // Query: abonnés actifs pour cette newsletter
        $qb = $this->em->createQueryBuilder()
            ->select('s', 'sub')
            ->from(Subscription::class, 'sub')
            ->join('sub.subscriber', 's')
            ->where('sub.newsletter = :nl')
            ->andWhere('sub.isActive = :active')
            ->setParameter('nl', $newsletter)
            ->setParameter('active', true);

        $query = $qb->getQuery();

        $sent = 0;
        $skipped = 0;

        $io->progressStart($limit ?? 0);

        foreach ($query->toIterable() as $subscription) {
            /** @var Subscription $subscription */
            $subscriber = $subscription->getSubscriber();
            $to = $subscriber->getEmail();

            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $io->writeln('[DRY-RUN] ' . $to);
            } else {
                $subject = sprintf('Newsletter %s', $newsletter->getName());
                $text = sprintf(
                    "Bonjour,\n\nVoici la newsletter \"%s\".\n\nBonne journée !",
                    $newsletter->getName()
                );

                $this->mailer->send(
                    (new Email())
                        ->from('no-reply@annexx.test')
                        ->to($to)
                        ->subject($subject)
                        ->text($text)
                );
            }

            $sent++;
            $io->progressAdvance();

            if ($limit !== null && $sent >= $limit) {
                break;
            }
        }

        $io->progressFinish();

        if ($dryRun) {
            $io->success(sprintf('DRY-RUN terminé. Destinataires listés: %d (skipped: %d)', $sent, $skipped));
        } else {
            $io->success(sprintf('Envoi terminé. Emails envoyés: %d (skipped: %d)', $sent, $skipped));
        }

        return Command::SUCCESS;
    }
}
