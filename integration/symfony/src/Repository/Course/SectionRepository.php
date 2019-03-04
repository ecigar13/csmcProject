<?php

namespace App\Repository\Course;

use App\Entity\Course\Course;
use App\Entity\User\User;
use Doctrine\ORM\EntityRepository;

class SectionRepository extends EntityRepository {
    public function findAllByStudent(User $student) {
        $qb = $this->createQueryBuilder('s');
        $qb->join('s.semester', 'm')
            ->where('m.active = 1')
            ->leftJoin('s.students', 'u')
            ->andWhere('u = :user')
            ->setParameters(array(
                'user' => $student
            ));
        $q = $qb->getQuery();
        return $q->getResult();
    }

    public function findByInstructor(User $user) {
        $qb = $this->createQueryBuilder('s');
        $qb->join('s.semester', 'm')
            ->where('m.active = 1')
            ->leftJoin('s.teaching_assistants', 'ta')
            ->leftJoin('s.instructors', 'i')
            ->where($qb->expr()->orX('i =:user', 'ta = :user'))
            ->setParameters(array(
                'user' => $user
            ));
        $q = $qb->getQuery();
        return $q->getResult();
    }

    public function findByUserAndCourse(User $user, Course $course) {
        $qb = $this->createQueryBuilder('s');
        $qb->leftJoin('s.students', 'u')
            ->where('u = :user')
            ->andWhere('s.course = :course')
            ->setParameters(array(
                'user' => $user,
                'course' => $course
            ));

        return $qb->getQuery()->getOneOrNullResult();
    }
}