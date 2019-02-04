<?php

namespace App\Controller;

use App\Entity\Misc\Subject;
use App\Entity\User\User;
use App\Utils\ImageEditor;
use Deployer\Exception\Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class ProfileController extends Controller {
    /**
     * @Route("/profile/{username}", name="profile")
     */
    public function profileAction(Request $request, User $user) {
        if(is_array($request->request->get('specialties'))) {
            foreach($request->request->get('specialties') as $subject_id => $rating) {
                $subject = $this->getDoctrine()
                    ->getRepository(Subject::class)
                    ->find($subject_id);

                $user->updateSpecialty($subject, $rating);
            }

            $this->getDoctrine()->getManager()->flush();
        }

        $subjects = $this->getDoctrine()
            ->getRepository(Subject::class)
            ->findAll();

        return $this->render('role/mentor/profile.html.twig', array(
            'user' => $user,
            'subjects' => $subjects
        ));
    }

    /**
     * @Route("/profile/{username}/image", name="profile_image")
     */
    public function imageAction(User $user, ImageEditor $imageEditor) {
        $image = $user->getProfilePicture();
        if(!$image) {
            throw new ResourceNotFoundException();
        }

        $cropped_image = $imageEditor->getCroppedImage($image);

        $image_name = $user->getUsername() . $image->get('extension');

        return $this->file(new File($cropped_image), $image_name, ResponseHeaderBag::DISPOSITION_INLINE);
    }
}