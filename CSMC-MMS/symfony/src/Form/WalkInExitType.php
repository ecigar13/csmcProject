<?php

namespace App\Form;


use App\Entity\User\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class WalkInExitType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('mentors', EntityType::class, array(
            'class' => User::class,
            'query_builder' => function(EntityRepository $er) {
                $qb = $er->createQueryBuilder('u')
                    ->join('u.roles', 'r')
                    ->where('r.name = :role')
                    ->setParameters(array(
                        'role' => 'mentor'
                    ));
                $qb->orderBy('u.firstName', 'ASC');
                return $qb;
            },
            'choice_label' => function ($user) {
                return $user->getPreferredName();
            },
            'multiple' => true
        ))->add('feedback', TextareaType::class, array(
            'required' => false,
            'attr' => array(
                'placeholder' => 'Feedback'
            )
        ))->add('user', HiddenType::class, array());
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'csrf_protection' => false //TODO enable and fix
        ));
    }
}