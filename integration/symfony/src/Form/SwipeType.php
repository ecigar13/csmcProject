<?php

namespace App\Form;

use App\Validator\Constraints\Scancode;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class SwipeType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('scancode', PasswordType::class, array(
            'constraints' => array(
                new NotBlank(),
                new Scancode()
            ),
            'label' => null,
            'error_bubbling' => true
        ))->add('session', HiddenType::class, array(
            'error_bubbling' => true,
            'required' => false
        ));
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'csrf_protection' => false //TODO enable and fix
        ));
    }
}