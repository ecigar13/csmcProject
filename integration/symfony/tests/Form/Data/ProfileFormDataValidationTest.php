<?php

namespace App\Tests\Form\Data;

use App\DataType\GraduationSemester;
use App\Form\Data\ProfileFormData;
use App\Tests\TestUtils\ReflectionUtils;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

class ProfileFormDataValidationTest extends TestCase
{
    /**
     * @dataProvider createValidPreferredNameData
     * @param string $preferredName
     * @throws \ReflectionException
     */
    public function testValidPreferredName(string $preferredName = null)
    {
        $this->validTest($preferredName, 'setPreferredName');

    }

    public function createValidPreferredNameData()
    {
        return [
            // Empty and null are valid values
            [null],
            [''],
            // We should accept any amount less than 17
            ['A'],
            ['WHO'],
            ['Mark'],
            // Maximum length is 17
            ['Anesthesiologists'],
            ['Bibliographically'],
        ];
    }

    /**
     * @dataProvider createInvalidPreferredNameData
     * @param string $preferredName
     * @throws \ReflectionException
     */
    public function testInvalidPreferredName(string $preferredName)
    {
        $this->invalidTest($preferredName, 'setPreferredName');
    }

    public function createInvalidPreferredNameData()
    {
        return [
            // Over 17 characters long
            ['Congregationalists'],
            ['Compartmentalizing'],
            ['RoundAndRoundAndRoundAgain'],
            ['ExtremelyLongNameThatShouldNeverBeAccepted']
        ];
    }

    /**
     * @dataProvider createValidBirthDateData
     * @param \DateTime $birthDate
     * @throws \ReflectionException
     */
    public function testValidBirthDate(\DateTime $birthDate)
    {
        $this->validTest($birthDate, 'setBirthDate');
    }

    public function createValidBirthDateData()
    {
        return array(
            [new \DateTime('-1 day')],
            [new \DateTime('01-01-2018')],
            [new \DateTime('01-01-1900')]
        );
    }

    /**
     * @dataProvider createInvalidBirthDateData
     * @param \DateTime $birthDate
     * @throws \ReflectionException
     */
    public function testInvalidBirthDate(\DateTime $birthDate = null)
    {
        $this->invalidTest($birthDate, 'setBirthDate');
    }

    public function createInvalidBirthDateData()
    {
        return array(
            [null],
            [new \DateTime()],
            [new \DateTime('tomorrow')],
            [new \DateTime('+1 week')],
            [new \DateTime('+1 month')],
            [new \DateTime('01-01-2050')]
        );
    }

    /**
     * @dataProvider createValidGraduationSemesterData
     * @param GraduationSemester $semester
     * @throws \ReflectionException
     */
    public function testValidGraduationSemester(GraduationSemester $semester)
    {
        $this->validTest($semester, 'setExpectedGraduationSemester');
    }

    public function createValidGraduationSemesterData()
    {
        $currentYear = (int)(new \DateTime())->format('Y');
        return array(
            [GraduationSemester::createFromArray(array('season' => GraduationSemester::SEASONS[0], 'year' => $currentYear))],
            [GraduationSemester::createFromArray(array('season' => GraduationSemester::SEASONS[1], 'year' => $currentYear))],
            [GraduationSemester::createFromArray(array('season' => GraduationSemester::SEASONS[2], 'year' => $currentYear))],
            [GraduationSemester::createFromArray(array('season' => GraduationSemester::SEASONS[0], 'year' => $currentYear + 5))],
            [GraduationSemester::createFromArray(array('season' => GraduationSemester::SEASONS[1], 'year' => $currentYear + 5))],
            [GraduationSemester::createFromArray(array('season' => GraduationSemester::SEASONS[2], 'year' => $currentYear + 5))],
        );
    }

    /**
     * @dataProvider createInvalidGraduationSemesterData
     * @param GraduationSemester $semester
     * @throws \ReflectionException
     */
    public function testInvalidGraduationSemester(GraduationSemester $semester = null)
    {
        $this->invalidTest($semester, 'setExpectedGraduationSemester');
    }

    public function createInvalidGraduationSemesterData()
    {
        $currentYear = (int)(new \DateTime())->format('Y');

        return array(
            [null],
            [GraduationSemester::createFromArray(array('season' => GraduationSemester::SEASONS[0], 'year' => $currentYear - 1))],
            [GraduationSemester::createFromArray(array('season' => GraduationSemester::SEASONS[1], 'year' => $currentYear - 1))],
            [GraduationSemester::createFromArray(array('season' => GraduationSemester::SEASONS[2], 'year' => $currentYear - 1))],
            [GraduationSemester::createFromArray(array('season' => GraduationSemester::SEASONS[0], 'year' => $currentYear - 5))],
            [GraduationSemester::createFromArray(array('season' => GraduationSemester::SEASONS[1], 'year' => $currentYear - 5))],
            [GraduationSemester::createFromArray(array('season' => GraduationSemester::SEASONS[2], 'year' => $currentYear - 5))],
        );
    }

    /**
     * @dataProvider createValidPhoneNumberData
     * @param string $phoneNumber
     * @throws \ReflectionException
     */
    public function testValidPhoneNumber(string $phoneNumber)
    {
        $this->validTest($phoneNumber, 'setPhoneNumber');
    }

    public function createValidPhoneNumberData()
    {
        return array(
            ['4698889999'],
            ['1234567890'],
            ['6666666666']
        );
    }

    /**
     * @dataProvider createInvalidPhoneNumberData
     * @param string $phoneNumber
     * @throws \ReflectionException
     */
    public function testInvalidPhoneNumber(string $phoneNumber = null)
    {
        $this->invalidTest($phoneNumber, 'setPhoneNumber');
    }

    public function createInvalidPhoneNumberData()
    {
        return array(
            [''],
            ['1'],
            ['12'],
            ['123456789'],
            ['12345678901'],
            ['phoneNumber'],
            ['(284)305-8978'],
            ['hi']
        );
    }

    /**
     * @dataProvider createValidLongFieldData
     * @param string|null $data
     * @throws \ReflectionException
     */
    public function testValidLongField(string $data = null)
    {
        $this->validTest($data, 'setDietaryRestrictions');
        $this->validTest($data, 'setAdminNotes');
    }

    public function createValidLongFieldData()
    {
        return array(
            [''],
            ['a'],
            ['Words'],
            [str_pad('Hello',250,'%')],
            [str_pad('Padding',250,'2')],
        );
    }

    /**
     * @param $value
     * @param string $setterName
     * @throws \ReflectionException
     */
    private function validTest($value, string $setterName)
    {
        $errors = $this->validateFieldValue($value, $setterName);

        $valueString = print_r($value, true);

        self::assertEquals(0, count($errors), "There should be no errors for valid value $valueString");
    }

    /**
     * @param $value
     * @param string $setterName
     * @throws \ReflectionException
     */
    private function invalidTest($value, string $setterName)
    {
        $errors = $this->validateFieldValue($value, $setterName);

        $valueString = print_r($value, true);
        self::assertEquals(1, count($errors), "There should be exactly one error for invalid value $valueString");
    }

    /**
     * @param $value
     * @param string $setterName
     * @return \Symfony\Component\Validator\ConstraintViolationListInterface
     * @throws \ReflectionException
     */
    private function validateFieldValue($value, string $setterName): \Symfony\Component\Validator\ConstraintViolationListInterface
    {
        // We don't want to load all the subjects from the database
        $form = $this->createValidForm();
        $form->$setterName($value);

        $validator = Validation::createValidatorBuilder()->enableAnnotationMapping()->getValidator();
        $errors = $validator->validate($form);
        return $errors;
    }

    /**
     * Since some fields have the not null requirement, we need to create a form that has values for those fields so that
     * they don't fail when testing other fields. This is kind of ugly but at least we can test each field separately.
     *
     * @return ProfileFormData
     * @throws \ReflectionException
     */
    private function createValidForm(): ProfileFormData
    {
        /** @var ProfileFormData $form */
        $form = ReflectionUtils::createWithoutConstructor(ProfileFormData::class);

        ReflectionUtils::assignValueToPrivateProperty($form, 'phoneNumber', '1234567890');
        ReflectionUtils::assignValueToPrivateProperty($form, 'birthDate', new \DateTime('-1 year'));
        ReflectionUtils::assignValueToPrivateProperty($form, 'expectedGraduationSemester',
            GraduationSemester::createFromArray(array('season' => GraduationSemester::SEASONS[0], 'year' => 2050)));

        return $form;
    }

}
