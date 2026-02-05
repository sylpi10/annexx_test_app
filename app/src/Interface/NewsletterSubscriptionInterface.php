<?php

namespace App\Interface;

use App\Service\Newsletter\SubscribeResult;

interface NewsletterSubscriptionInterface
{
    /**
     * @return array
     */
    public function getNewsletters(): array;

    /**
     * @param string $email
     * @param string $birthDateRaw
     * @param array $newsletterIds
     * @return SubscribeResult
     */
    public function subscribe(string $email, string $birthDateRaw, array $newsletterIds): SubscribeResult;

}
