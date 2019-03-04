<?php

namespace App\Form;


use App\DataType\GraduationSemester;
use App\Form\Data\ProfileFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('preferredName')
            ->add('birthDate', BirthdayType::class, array(
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd'
            ))
            ->add('expectedGraduationSemester', GraduationSemesterType::class)
            ->add('phoneNumber', TelType::class)
            ->add('specialties', CollectionType::class, array(
                'allow_add' => false,
                'entry_type' => SpecialtyType::class,
                'label' => false
            ))
            ->add('dietaryRestrictions', TextareaType::class, array(
                'required' => false
            ))
            ->add('notificationPreferences', NotificationPreferencesType::class)
            ->add('submit', SubmitType::class);

        if ($options['is_admin']) {
            $builder->add('adminNotes', TextareaType::class, array(
                'required' => false
            ));
        }

        $builder->get('expectedGraduationSemester')->addViewTransformer(new CallbackTransformer(
            function ($semester) {
                if (!is_null($semester)) {
                    return array(
                        'season' => $semester->getSeason(),
                        'year' => $semester->getYear());
                }

                return null;
            },
            function ($array) {
                return GraduationSemester::createFromArray($array);
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => ProfileFormData::class,
            'is_admin' => false
        ));
    }

}