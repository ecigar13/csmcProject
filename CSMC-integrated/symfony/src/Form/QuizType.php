<?php

namespace App\Form;

use App\Entity\Misc\Room;
use App\Entity\Session\Quiz;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;

class QuizType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('session', SessionType::class, array(
            'data_class' => Quiz::class,
            'label' => false
        ))->add('room', EntityType::class, array(
            'class' => Room::class
        ))->add('startDate', DateType::class, array(
            'html5' => true,
            'widget' => 'single_text',
            'format' => DateType::HTML5_FORMAT
        ))->add('endDate', DateType::class, array(
            'html5' => true,
            'widget' => 'single_text',
            'format' => DateType::HTML5_FORMAT
        ));
    }


}