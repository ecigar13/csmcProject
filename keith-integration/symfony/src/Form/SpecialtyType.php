<?php

namespace App\Form;


use App\Form\Data\SpecialtyFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SpecialtyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('rating', RangeType::class, array(
            'attr' => array(
                'min' => 1,
                'max' => 5,
                'step' => 1
            )
        ));
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['label'] = false;
        $topicName = $form->getData()->getTopic()->getName();
        $view['rating']->vars['form']->vars['label'] = $topicName;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => SpecialtyFormData::class
        ));
    }

}