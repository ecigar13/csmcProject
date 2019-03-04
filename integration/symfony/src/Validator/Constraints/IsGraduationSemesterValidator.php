<?php


namespace App\Validator\Constraints;


use App\DataType\GraduationSemester;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class IsGraduationSemesterValidator extends ConstraintValidator
{

    /**
     * Checks if the passed value is valid.
     *
     * @param mixed $value The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     */
    public function validate($value, Constraint $constraint)
    {
        if (is_null($value)) {
            return;
        }

        if (is_null($value->getYear())) {
            $this->addViolation('Graduation year cannot be empty');
        } elseif (is_null($value->getSeason())) {
            $this->addViolation('Graduation semester cannot be empty');
        } else {
            if (!in_array($value->getSeason(), GraduationSemester::SEASONS)) {
                $this->addViolation(sprintf('Semester must be one of %s', implode(', ', GraduationSemester::SEASONS)));
            }

            $currentYear = (integer)(new \DateTime())->format('Y');

            if ($value->getYear() < $currentYear) {
                    $this->addViolation('Graduation year cannot be in the past');
            }
        }
    }

    public function addViolation(string $message)
    {
        $this->context->buildViolation($message)
            ->addViolation();
    }
}