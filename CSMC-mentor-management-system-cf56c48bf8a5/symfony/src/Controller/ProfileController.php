<?php

namespace App\Controller;

use App\DataTransferObject\FileData;
use App\Entity\Misc\Subject;
use App\Entity\File\File;
use App\Entity\User\User;
use App\Form\Data\ProfileFormData;
use App\Form\ProfileType;
use App\Utils\ImageEditor;
use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class ProfileController extends Controller
{
    /**
     * @Route("/profile/{username}", name="profile")
     */
    public function viewProfile(Request $request, User $user)
    {
        $isAdmin = $this->isGranted('admin');

        // Protect against non-admin user trying to view someone else's profile
        if ($this->getUser() != $user && !$isAdmin) {
            // Redirect to home instead of displaying a forbidden message
            return $this->redirectToRoute('home');
        }

        return $this->render('role/mentor/profile.html.twig', array(
            'user' => $user,
            'isAdmin' => $isAdmin
        ));
    }

    /**
     * @Route("/profile/{username}/edit", name="edit_profile")
     */
    public function editProfile(Request $request, User $mentor)
    {
        // Protect against non-admin user trying to edit someone else's profile
        $isAdmin = $this->isGranted('admin');
        if ($this->getUser() != $mentor && !$isAdmin) {
            // Redirect to home instead of displaying a forbidden message
            return $this->redirectToRoute('home');
        }

        $form = $this->createForm(ProfileType::class,
            ProfileFormData::createFromProfile($mentor->getProfile(), $this->getDoctrine()->getManager()),
            array('is_admin' => $isAdmin));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $mentor->getProfile()->updateFromFormData($form->getData(), $isAdmin);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($mentor);
            $entityManager->flush();
            return $this->redirectToRoute('profile', array(
                'username' => $mentor->getUsername()
            ));
        }

        return $this->render('role/mentor/edit_profile.html.twig', array(
            'user' => $mentor,
            'form' => $form->createView(),
            'isAdmin' => $isAdmin
        ));
    }

    /**
     * @Route("/profile/{username}/save_image", name="save_profile_image")
     */
    public function saveImageAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new MethodNotAllowedException();
        }
        $file = new
        FileData();
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

        $isAdmin = $this->isGranted('admin');
        $user->updateProfilePicture($file, $isAdmin);

        $em->flush();

        return new Response('success', 200);
    }

    /**
     * @Route("/profile/{username}/update_image", name="update_profile_image")
     */
    public function updateImageAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new MethodNotAllowedException();
        }
        $user_id = $request->request->get('user');
        $user = $this->getDoctrine()->getRepository(User::class)->find($user_id);
        $file = $user->getProfile()->getProfilePictureModificationRequest()->getValue();

        $crop = $request->request->get('crop');
        $canvas = $request->request->get('canvas');
        $image = $request->request->get('image');

        $this->get('logger')->debug($crop);

        $file->set('crop', $crop);
        $file->set('canvas', $canvas);
        $file->set('image', $image);

        $em = $this->getDoctrine()->getManager();
        $em->persist($file);

        $em->flush();

        return new Response('success', 200);
    }

    /**
     * @Route("/profile/{username}/image", name="profile_image")
     */
    public function imageAction(User $user, ImageEditor $imageEditor)
    {
        $image = $user->getProfilePicture();
        $image_name = $user->getUsername() . $image->get('extension');

        return $this->cropImage($imageEditor, $image_name, $image);
    }

    /**
     * @Route("/profile/{username}/new_image", name="profile_requested_image")
     * @param User $user
     * @param ImageEditor $editor
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function requestedProfileImage(User $user, ImageEditor $editor)
    {
        $image = $user->getProfile()->getProfilePictureModificationRequest()->getValue();
        $name = $user->getUsername() . '-request' . $image->get('extension');

        return $this->cropImage($editor, $name, $image);
    }

    /**
     * @Route("/profile/{username}/origin_requested_image", name="origin_requested_image")
     * @param User $user
     * @param ImageEditor $editor
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function originRequestedImage(User $user, ImageEditor $editor)
    {
        $image = $user->getProfile()->getProfilePictureModificationRequest()->getValue();
        $name = $user->getUsername() . '-origin.jpeg' . $image->get('extension');
        $origin_image = $editor->getOriginImage($image);
        return $this->file($origin_image, $name, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    /**
     * @param ImageEditor $imageEditor
     * @param string $image_name
     * @param File $image
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    private function cropImage(ImageEditor $imageEditor, string $image_name, File $image = null)
    {
        if (!$image) {
            throw new ResourceNotFoundException();
        }

        $cropped_image = $imageEditor->getCroppedImage($image);

        return $this->file($cropped_image, $image_name, ResponseHeaderBag::DISPOSITION_INLINE);
    }


}