<?php

namespace App\Form;

use App\Entity\Misc\Room;
use App\Entity\Session\ScheduledSession;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
use Symfony\Component\Form\FormBuilderInterface;

class ScheduledSessionType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('session', SessionType::class, array(
            'data_class' => ScheduledSession::class,
            'label' => false
        ))->add('repeats', ChoiceType::class, array(
            'choices' => range(1, 50),
            'choice_label' => function ($choice) {
                return $choice;
            },
        ))->add('defaultLocation', EntityType::class, array(
            'class' => Room::class
        ))->add('defaultCapacity', ChoiceType::class, array(
            'choices' => range(1, 50),
            'choice_label' => function ($choice) {
                return $choice;
            },
        ))->add('defaultDuration', DateIntervalType::class, array(
            'with_years' => false,
            'with_months' => false,
            'with_days' => false,
            'with_hours' => true,
            'with_minutes' => true
        ));
    }
}