<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ScancodeValidator extends ConstraintValidator {

    private $legacyPattern = '/603[0-9]{13}/';
    private $pattern = '/[A-Z0-9]{6}:[A-Z0-9]{4}/i';

    public function validate($value, Constraint $constraint) {
        $legacy = preg_match($this->legacyPattern, $value, $matches) === 1;
        $current = preg_match($this->pattern, $value, $matches) === 1;

        if (!$legacy && !$current) {
            $this->context
                ->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
