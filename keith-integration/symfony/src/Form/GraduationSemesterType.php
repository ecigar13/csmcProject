<?php


namespace App\Form;


use App\DataType\GraduationSemester;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class GraduationSemesterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $isRequired = $options['required'];
        $builder->add('season', ChoiceType::class, array(
            'label' => false,
            'choices' => Utils::createOptionsArray(GraduationSemester::SEASONS),
            'placeholder' => 'Select Semester',
            'required' => $isRequired
        ));
        $builder->add('year', ChoiceType::class, array(
            'label' => false,
            'choices' => Utils::createOptionsArray($this->getYearChoices()),
            'placeholder' => 'Select Year',
            'required' => $isRequired
        ));
    }

    private function getYearChoices()
    {
        $currentYear = (int)(new \DateTime())->format('Y');

        return Utils::createOptionsArray(range($currentYear, $currentYear + 5));
    }
}