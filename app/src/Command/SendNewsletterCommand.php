<?php

namespace App\Command;

use App\Interface\NewsletterInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-newsletter',
    description: 'Envoie une newsletter à tous les abonnés actifs (Subscription.isActive=true) pour une newsletter donnée.'
)]
class SendNewsletterCommand extends Command
{
    public function __construct(
        protected NewsletterInterface $sender
    ) {
        parent::__construct();
    }

    /**
     * @return void
     */
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
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $newsletterId = (int) $input->getArgument('newsletterId');
        $dryRun = (bool) $input->getOption('dry-run');
        $limitOpt = $input->getOption('limit');
        $limit = $limitOpt !== null ? max(1, (int) $limitOpt) : null;

        $io->title(sprintf('Envoi newsletter #%d', $newsletterId));

        $result = $this->sender->sendById(
            $newsletterId,
            dryRun: $dryRun,
            limit: $limit,
            onRecipient: function(string $email) use ($io, $dryRun) {
                if ($dryRun) {
                    $io->writeln('[DRY-RUN] '.$email);
                }
            }
        );

        $io->success(sprintf(
            'Terminé. sent=%d skipped=%d errors=%d',
            $result->sent,
            $result->skippedInvalidEmail,
            $result->errors
        ));

        return Command::SUCCESS;
    }

}
