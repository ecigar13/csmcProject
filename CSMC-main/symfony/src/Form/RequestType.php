<?php

namespace App\Form;


use App\Entity\Course\Section;
use App\Entity\File\File;
use App\Entity\Session\SessionType;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RequestType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $user = $options['user'];

        $builder->add('topic', TextType::class)
            ->add('studentInstructions', TextareaType::class)
            ->add('type', EntityType::class, array(
                'class' => SessionType::class,
                'choice_label' => function ($type) {
                    return $type->getName();
                }
            ))
            ->add('startDate', DateType::class, array(
                'html5' => true,
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => 'yyyy/mm/dd'
            ))
            ->add('endDate', DateType::class, array(
                'html5' => true,
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
            ))
            ->add('sections', EntityType::class, array(
                'class' => Section::class,
                'query_builder' => function (EntityRepository $er) use ($user) {
                    $qb = $er->createQueryBuilder('s');
                    return $qb
                        // ->leftJoin('s.teaching_assistants', 'ta')
                        // ->join('s.semester', 'm')
                        ->join('s.instructors', 'i')
                        ->where('i = :instructor')
                        // ->where($qb->expr()->orX('i = :instructor', 'ta = :instructor'))
                        // ->andWhere('m.active = 1')
                        ->join('s.course', 'c')
                        ->addOrderBy('c.number')
                        ->addOrderBy('s.number')
                        ->setParameter('instructor', $user);
                },
                'choice_label' => function ($section) {
                    $course = $section->getCourse();
                    return $course->getDepartment()
                               ->getAbbreviation() . ' ' . $course->getNumber() . '.' . $section->getNumber();
                },
                'multiple' => true

            ))->add('files', FileType::class, array(
                'multiple' => true,
                'data_class' => null,
                'required' => false
            ))
            // ->add('files', EntityType::class, array(
            //     'label' => 'Uploaded Files',
            //     'multiple' => true,
            //     'expanded' => true,
            //     'choice_label' => 'name',
            //     'class' => File::class,
            //     'choices' => $builder->getData()->getFiles(),
            //     'required' => false
            // ))
            ->add('submit', SubmitType::class);

        // $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
        //     $request = $event->getData();
        //     $form = $event->getForm();
        //     if (!$request->getFiles()) {
        //         $form->remove('files');
        //     }
        // });
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'class' => 'AppBundle\Entity\Session\Request'
        ));
        $resolver->setRequired(array(
            'user'
        ));
    }
}