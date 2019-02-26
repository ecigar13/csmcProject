<?php

namespace App\Form;

use App\Entity\Misc\Room;
use App\Entity\Schedule\ShiftAssignment;
use App\Entity\User\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TimeSlotType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('date', DateType::class, array(
                'disabled' => false,
                'html5' => true,
                'widget' => 'single_text',
                'format' => DateType::HTML5_FORMAT
            ))
            ->add('startTime', TimeType::class, array(
                'disabled' => false,
                'html5' => true,
                'widget' => 'single_text'
            ))
            ->add('endTime', TimeType::class, array(
                'disabled' => false,
                'html5' => true,
                'widget' => 'single_text'
            ))
            ->add('location', EntityType::class, array(
                'class' => Room::class,
                'placeholder' => 'Choose a room',
            ))
            ->add('capacity', ChoiceType::class, array(
                'choices' => range(1, 50),
                'choice_label' => function ($choice) {
                    return $choice;
                },
            ))
            ->add('assignments', EntityType::class, array(
                'class' => ShiftAssignment::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('a');
                },
                'choice_label' => function ($assignment) {
                    return $assignment->getMentor();
                },
                'multiple' => true,
            ))
            ->add('session', HiddenType::class)
            ->add('start', HiddenType::class)
            ->add('end', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'csrf_protection' => false // TODO change this soon
        ));
    }
}