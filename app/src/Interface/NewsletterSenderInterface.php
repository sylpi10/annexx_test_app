<?php

namespace App\Interface;

use App\Entity\Newsletter;
use App\Service\Newsletter\SendResult;

interface NewsletterSenderInterface
{
    public function sendNewsletter(Newsletter $newsletter, bool $dryRun = false, ?int $limit = null, ?callable $onRecipient = null): SendResult;

    public function sendById(int $newsletterId, bool $dryRun, ?int $limit, ?callable $onRecipient): SendResult;

}
