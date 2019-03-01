<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Scancode extends Constraint {
    public $message = 'The scancode is not valid. Please try swiping again.';
}
