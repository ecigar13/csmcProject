<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CardRegisterType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('username', TextType::class, array(
            'label' => 'NetID'
        ))->add('password', PasswordType::class, array(

        ))->add('swipe', HiddenType::class, array(

        ))->add('session', HiddenType::class, array(
            'required' => false
        ));//->add('submit', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'csrf_protection' => false //TODO enable and fix
        ));
    }
}