<?php

namespace App\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

/**
 * Protocol Enum
 *
 * Represents the available protocols for domain configuration.
 * Used to specify whether a domain should use HTTP or HTTPS protocol
 * for serving shortened links and admin panel access.
 *
 * @see \App\Models\Domain
 */
enum Protocol: string implements HasDescription, HasLabel
{
    case HTTP = 'http';
    case HTTPS = 'https';

    public function getDescription(): string
    {
        return match ($this) {
            self::HTTP => 'Unencrypted protocol, not recommended for production.',
            self::HTTPS => 'Secure protocol with SSL/TLS encryption.',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::HTTP => 'HTTP',
            self::HTTPS => 'HTTPS',
        };
    }
}
