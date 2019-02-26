<?php

namespace App\Controller;

use App\DataTransferObject\FileData;
use App\Entity\Course\Department;
use App\Entity\File\File;
use App\Entity\User\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;

/**
 * @Route("/admin", name="admin_")
 */
class AdminController extends Controller {
    /**
     * @Route("/", name="home")
     * @param Request $request
     *
     * @return Response
     */
    public function homeAction(Request $request) {
        return $this->render('role/admin/base.html.twig');
    }

    /**
     * @Route("/cropper", name="cropper")
     */
    public function cropperAction(Request $request) {
        $mentors = $this->getDoctrine()
            ->getRepository(User::class)
            ->findByRole('mentor');

        return $this->render('role/admin/cropper.html.twig', array(
            'mentors' => $mentors
        ));
    }

    /**
     * @Route("/ajax/image_upload", name="image_upload")
     */
    public function mentorFileUpload(Request $request) {
        if (!$request->isXmlHttpRequest()) {
            throw new MethodNotAllowedException();
        }

        $file = new FileData();
        $file->file = $request->files->get('file');
        $crop = $request->request->get('crop');
        $canvas = $request->request->get('canvas');
        $image = $request->request->get('image');

        $this->get('logger')->debug($crop);

        $em = $this->getDoctrine()->getManager();

        $file = File::fromUploadData($file, $em, array(
            'crop' => $crop,
            'canvas' => $canvas,
            'image' => $image
        ));

        $em->persist($file);

        $user_id = $request->request->get('user');
        $user = $this->getDoctrine()->getRepository(User::class)->find($user_id);

        $user->updateProfilePicture($file);

        $em->flush();

        return new Response('success', 200);
    }
}