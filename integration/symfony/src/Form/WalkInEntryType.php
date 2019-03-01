<?php

namespace App\Form;

use App\Entity\Course\Course;
use App\Entity\Session\Quiz;
use App\Entity\Session\WalkInActivity;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class WalkInEntryType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('topic', TextType::class, array(
            'constraints' => array(
                new Length(array(
                    'max' => 32
                ))
            ),
            'attr' => array(
                'maxlength' => 32
            ),
        ))->add('activity', EntityType::class, array(
            'class' => WalkInActivity::class,
            'choice_label' => 'name',
            'placeholder' => 'Activity'
        ))->add('quiz', EntityType::class, array(
            'class' => Quiz::class,
            'query_builder' => function (EntityRepository $er) {
                $day = (new \DateTime())->format('Y/m/d');
                $qb = $er->createQueryBuilder('q');
                $qb->join('q.timeSlot', 't')
                    ->where($qb->expr()->lte('t.startTime', ':day'))
                    ->andWhere($qb->expr()->gte('t.endTime', ':day'))
                    ->setParameter('day', $day);
                return $qb;
            },
            'choice_label' => 'topic',
            'required' => false,
            'attr' => array(
                'hidden' => 'hidden'
            ),
        ))->add('course', EntityType::class, array(
            'class' => Course::class,
            'query_builder' => function (EntityRepository $er) use ($options) {
                return $er->createQueryBuilder('s')
                    ->where('s.supported = true')
                    ->orderBy('s.number');
            },
            'choice_label' => function (Course $course) {
                return $course->getDepartment()->getAbbreviation() . ' ' . $course->getNumber();
            },
            'placeholder' => 'Course'
        ))->add('otherCourse', EntityType::class, array(
            'class' => Course::class,
            'query_builder' => function (EntityRepository $er) use ($options) {
                return $er->createQueryBuilder('s')
                    ->where('s.supported = false')
                    ->orderBy('s.number');
            },
            'choice_label' => function (Course $course) {
                return $course->getDepartment()->getAbbreviation() . ' ' . $course->getNumber();
            },
            'placeholder' => '',
            'required' => false,
            'mapped' => false,
            'attr' => array(
                'hidden' => 'hidden'
            ),
        ))->add('user', HiddenType::class, array(

        ));
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'csrf_protection' => false //TODO enable and fix
        ));
    }
}