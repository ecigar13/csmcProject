<?php


namespace App\Form;


use App\Form\Data\NotificationPreferencesFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NotificationPreferencesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('preferredEmail', EmailType::class, array(
                'required' => false
            ))
            ->add('useEmail', CheckboxType::class, array(
                'required' => false
            ))
            ->add('preferredPhoneNumber', TelType::class, array(
                'required' => false
            ))
            ->add('preferredPhoneNumberCarrier', ChoiceType::class, array(
                'choices' => array_combine(
                    NotificationPreferencesFormData::getPhoneNumberCarrierChoices(),
                    NotificationPreferencesFormData::getPhoneNumberCarrierChoices()),
                'placeholder' => 'Select a Carrier',
                'required' => false
            ))
            ->add('usePhoneNumber', CheckboxType::class, array(
                'required' => false
            ))
            ->add('notifyWhenAssigned', CheckboxType::class, array(
                'required' => false
            ))
            ->add('notifyBeforeSession', CheckboxType::class, array(
                'required' => false
            ))
            ->add('sessionReminderAdvanceDays', NumberType::class, array(
                'required' => false
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => NotificationPreferencesFormData::class
        ));
    }

}