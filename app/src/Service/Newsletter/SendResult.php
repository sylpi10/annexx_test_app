<?php

namespace App\Service\Newsletter;

final class SendResult
{
    public function __construct(
        public int $processed = 0,
        public int $sent = 0,
        public int $skippedInvalidEmail = 0,
        public int $errors = 0,
    ) {}
}
