<?php

namespace App\Form;

use App\Entity\Course\Section;
use App\Entity\File\File;
use Doctrine\DBAL\Types\BooleanType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SessionType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('topic')
            ->add('type', EntityType::class, array(
                'class' => \App\Entity\Session\SessionType::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('t');
                },
                'choice_label' => 'name'
            ))->add('description', TextareaType::class, array(
                'required' => false
            ))->add('studentInstructions', TextareaType::class, array(
                'required' => false
            ))->add('mentorInstructions', TextareaType::class, array(
                'required' => false
            ))->add('sections', EntityType::class, array(
                'class' => Section::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('s')
                        ->join('s.semester', 'm')
                        ->where('m.active = 1')
                        ->join('s.course', 'c')
                        ->addOrderBy('c.number')
                        ->addOrderBy('s.number');
                },
                'choice_label' => function ($section) {
                    $course = $section->getCourse();
                    return $course->getDepartment()
                               ->getAbbreviation() . ' ' . $course->getNumber() . '.' . $section->getNumber();
                },
                'multiple' => true
            ))->add('graded', CheckboxType::class, array(
                'required' => false
            ))->add('numericGrade', CheckboxType::class, array(
                'required' => false
            ))->add('files', FileType::class, array(
                'multiple' => true
            ))->add('uploadedFiles', ChoiceType::class, array(
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ))
            ->add('color', ColorType::class)
            ->add('request', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'inherit_data' => true,
        ));
    }
}