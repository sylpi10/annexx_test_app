<?php

namespace App\Interface;

use App\Entity\Newsletter;
use App\Service\Newsletter\SendResult;
use Symfony\Component\Mime\Email;

interface NewsletterInterface
{
    public function sendNewsletter(Newsletter $newsletter, bool $dryRun = false, ?int $limit = null, ?callable $onRecipient = null): SendResult;

//    public function buildEmail(Newsletter $newsletter, string $to): Email;

    public function sendById(int $newsletterId, bool $dryRun, ?int $limit, ?callable $onRecipient): SendResult;

}
