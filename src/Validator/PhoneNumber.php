<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
class PhoneNumber extends Constraint
{
    /**
     * @param string|null $region Code pays ISO 3166-1 alpha-2 (ex. "GA") auquel le numéro doit appartenir.
     *                            Laisser à null pour accepter n'importe quel numéro international valide.
     */
    public function __construct(
        public ?string $region = null,
        public string $message = 'Le numéro doit être un numéro de téléphone valide (format international, ex. {{ example }}).',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);
    }
}
