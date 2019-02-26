<?php

namespace App\Form;

use App\Entity\Schedule\ShiftAssignment;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AbsenceType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $user = $options['user'];
        $builder
            // ->add('assignment', EntityType::class, array(
            //     'class' => ShiftAssignment::class,
            //     'choice_label' => function ($shift) {
            //         $scheduled_shift = $shift->getScheduledShift();
            //         $shift_time = $scheduled_shift->getShift()->getStartTime();
            //         return $scheduled_shift->getDate()->format('m/d/y') .
            //                ' ' . $shift_time->format('g:i A');
            //     },
            //     'query_builder' => function (EntityRepository $er) use ($user) {
            //         $qb = $er->createQueryBuilder('sa');
            //         return $qb->join('sa.scheduledShift', 'ss')
            //             ->join('ss.shift', 's')
            //             ->where('sa.mentor = :user')
            //             // ->andWhere('ss.date >= :today')
            //             //TODO figure out how to remove shifts that are absent but include them in edit
            //             //   ->andWhere($qb->expr()->isNull('sa.absence'))
            //             //->orWhere()
            //             ->orderBy('s.startTime')
            //             ->orderBy('ss.date')
            //             ->setParameters(array(
            //                 'user' => $user,
            //                 // 'today' => (new \DateTime())
            //             ));
            //     },
            //     'label' => 'Shift'
            // ))
            ->add('date', DateType::class, array(
                'html5' => true,
                'widget' => 'single_text',
                'format' => DateType::HTML5_FORMAT
            ))->add('shift', EntityType::class, array(
                'class' => ShiftAssignment::class,
                'choice_label' => function ($shift) {
                    $scheduled_shift = $shift->getScheduledShift();
                    $shift_time = $scheduled_shift->getShift()->getStartTime();
                    return $shift_time->format('g:i A');
                },
                'query_builder' => function (EntityRepository $er) use ($user) {
                    $qb = $er->createQueryBuilder('sa');
                    return $qb->join('sa.scheduledShift', 'ss')
                        ->join('ss.shift', 's')
                        ->where('sa.mentor = :user')
                        ->orderBy('s.startTime')
                        ->orderBy('ss.date')
                        ->setParameters(array(
                            'user' => $user
                        ));
                },
                'label' => 'Shift'
            ))->add('reason', TextareaType::class)
            ->add('submit', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'user' => null
        ));
    }
}