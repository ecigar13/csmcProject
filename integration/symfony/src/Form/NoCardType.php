<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class NoCardType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('reason', ChoiceType::class, array(
            'placeholder' => '',
            'choices' => array(
                'Didn\'t think I needed it' => 'unneeded',
                'I forgot to bring it' => 'forgot',
                'It was lost or stolen' => 'lost'
                // TODO maybe add other with text input
            )
        ))->add('username', TextType::class, array(

        ))->add('password', PasswordType::class, array(

        ))->add('session', HiddenType::class, array(

        ));//->add('submit', SubmitType::class);
    }
}