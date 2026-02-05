<?php

namespace App\Service\Newsletter;

final class SubscribeResult
{
    public function __construct(
        public bool $success,
        public bool $isActive,
        /** @var string[] */
        public array $newsletterNames = [],
        public ?string $error = null,
        public ?string $info = null,
    ) {}
}
