<?php

namespace App\Validator;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class PhoneNumberValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof PhoneNumber) {
            throw new UnexpectedTypeException($constraint, PhoneNumber::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!\is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        $util = PhoneNumberUtil::getInstance();

        try {
            $parsed = $util->parse($value, $constraint->region);
        } catch (NumberParseException) {
            $this->addViolation($constraint);

            return;
        }

        $isValid = $constraint->region !== null
            ? $util->isValidNumberForRegion($parsed, $constraint->region)
            : $util->isValidNumber($parsed);

        if (!$isValid) {
            $this->addViolation($constraint);
        }
    }

    private function addViolation(PhoneNumber $constraint): void
    {
        $util = PhoneNumberUtil::getInstance();
        $example = $constraint->region !== null
            ? $util->getExampleNumberForType($constraint->region, PhoneNumberType::MOBILE)
            : $util->getExampleNumber('BJ');

        $exampleFormatted = $example !== null
            ? $util->format($example, PhoneNumberFormat::E164)
            : '+22912345678';

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ example }}', $exampleFormatted)
            ->addViolation();
    }
}
