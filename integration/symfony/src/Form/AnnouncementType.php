<?php

namespace App\Form;

use App\Entity\User\Role;
use App\Entity\User\UserGroup;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class AnnouncementType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('subject', TextType::class, array(
            'error_bubbling' => true,
        ))->add('message', TextareaType::class, array(
            'error_bubbling' => true,
        ))->add('active', CheckboxType::class, array(
            'required' => false
        ))->add('roles', EntityType::class, array(
            // looks for choices from this entity
            'class' => Role::class,
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('u')
                    ->orderBy('u.name', 'ASC');
            },
            'choice_label' => function ($roles) {
                return $roles->getName();
            },
            // 'choice_label' => 'name',
            'multiple' => true,
            'required' => false
        // ))->add('userGroups', EntityType::class, array(
        //     // looks for choices from this entity
        //     'class' => UserGroup::class,
        //     'query_builder' => function (EntityRepository $er) {
        //         return $er->createQueryBuilder('ug')
        //             ->orderBy('ug.name', 'ASC');
        //     },
        //     'choice_label' => 'name',
        //     'multiple' => true,
        //     'required' => false
        ))->add('startDate', DateType::class, array(
            'html5' => true,
            'widget' => 'single_text',
            'format' => 'yyyy-MM-dd',
            'placeholder' => 'yyyy/mm/dd',
        ))->add('endDate', DateType::class, array(
            'html5' => true,
            'widget' => 'single_text',
            'format' => 'yyyy-MM-dd',
            'placeholder' => 'yyyy/mm/dd'
        ))->add('submit', SubmitType::class, array());
    }
}