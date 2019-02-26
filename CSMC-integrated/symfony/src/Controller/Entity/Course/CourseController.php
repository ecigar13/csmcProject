<?php

namespace App\Controller\Entity\Course;

use App\Entity\Course\Course;
use App\Entity\Course\Section;
use App\Entity\Misc\Semester;
use App\Entity\User\Role;
use App\Entity\User\User;
use App\Form\Course\CourseType;
use Doctrine\Common\Collections\Criteria;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class CourseController extends Controller {
    /**
     * @Route("/course", name="course")
     */
    public function courseAction() {
        return $this->forward('App:Entity/Course/Section:section');
    }
}