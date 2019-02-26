<?php

namespace App\Controller\Entity\User;

use App\Entity\User\Info\DietaryRestriction;
use App\Entity\User\Info\DietaryRestrictionCategory;
use App\Form\User\DietaryRestrictionCategoryType;
use App\Form\User\DietaryRestrictionType;
use App\Form\User\InfoType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use App\Entity\Misc\File;

class InfoController extends Controller {
    /**
     * @Route("/user/info/view/{id}", name="user_info_view")
     */
    public function infoViewAction(Request $request, $id = null) {
        $this->denyAccessUnlessGranted(['admin', 'mentor', 'user']);
        if($id) {
            $user = $this->getDoctrine()->getRepository('App\User:User')->find($id);
            if(!$user) {
                throw $this->createNotFoundException('User not found!');
            }
            if(!$this->isGranted(['admin', 'user']) && $id != $this->getUser()->getId()) {
                return $this->createAccessDeniedException();
            }
        } else {
            $user = $this->getUser();
        }

        return $this->render('user/profile.html.twig', array(
            'user' => $user
        ));
    }

    /**
     * @Route("/user/info/edit/{id}", name="user_info_edit")
     */
    public function infoEditAction(Request $request, $id) {
        $user = $this->getDoctrine()->getRepository('App\User:User')->find($id);

        if(!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $isUser = false;
        if($this->getUser()->getId() == $id) {
            $isUser = true;
        }

        if(!$isUser && !$this->isGranted(['admin', 'user'])) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(InfoType::class, $user->getInfo(), array(
            'entity_manager' => $this->getDoctrine()->getManager()
        ));

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $info = $form->getData();
            $uploadedImage = $info->getUploadedImage();

            if(!empty($uploadedImage)) {
                $file = new File();

                $file->setName($uploadedImage->getClientOriginalName());
                $file->setType($uploadedImage->getClientMimeType());
                $file->setExtension($uploadedImage->guessExtension());
                $file->setSize($uploadedImage->getSize());
                $file->setData(stream_get_contents(fopen($uploadedImage->getRealPath(), 'rb')));

                $info->setImage($file);

                $this->getDoctrine()->getManager()->persist($file);
            }

            $this->getDoctrine()->getManager()->flush();

            if($isUser) {
                $this->addFlash('notice','Settings saved for your information page!');
            } else {
                $this->addFlash('notice','Settings saved for ' . $user->getUsername());
            }

            return $this->redirectToRoute('user_info_view', ['id' => $user->getId()]);
        }

        return $this->render('shared/form/form.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/user/info/dietary_restriction", name="user_info_dietary_restriction")
     */
    public function infoDietaryRestrictionAction(Request $request) {
        $this->denyAccessUnlessGranted(['admin', 'developer', 'user']);

        $categories = $this->getDoctrine()
            ->getRepository('App\User:Info\DietaryRestrictionCategory')
            ->findAll();
        $restrictions = $this->getDoctrine()
            ->getRepository('App\User:Info\DietaryRestriction')
            ->findAll();

        return $this->render('user/dietary_restriction.html.twig', array(
            'categories' => $categories,
            'restrictions' => $restrictions
        ));
    }

    /**
     * @Route("/user/info/dietary_restriction/create", name="user_info_dietary_restriction_create")
     */
    public function infoDietaryRestrictionCreateAction(Request $request) {
        $this->denyAccessUnlessGranted(['admin', 'developer', 'user']);
        
        $restriction = new DietaryRestriction();
        $form = $this->createForm(DietaryRestrictionType::class, $restriction);
        
        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid()) {
            $restriction = $form->getData();
            $em = $this->getDoctrine()->getManager();
            $em->persist($restriction);
            $em->flush();

            $this->addFlash('notice', 'Successfully created restriction "' . $restriction->getName() . '"');
            unset($restriction);
            unset($form);
            $restriction = new DietaryRestriction();
            $form = $this->createForm(DietaryRestrictionType::class, $restriction);
        }
        
        return $this->render('shared/form/form.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/user/info/dietary_restriction/edit/{id}", name="user_info_dietary_restriction_edit")
     */
    public function infoDietaryRestrictionEditAction(Request $request, $id) {
        $this->denyAccessUnlessGranted(['admin', 'developer', 'user']);

        $restriction = $this->getDoctrine()->getRepository('App\User:Info\DietaryRestriction')->find($id);

        if (empty($restriction)) {
            throw $this->createNotFoundException('Restriction with id ' . $id . ' not found');
        } else {
            $form = $this->createForm(DietaryRestrictionType::class, $restriction);

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $restriction = $form->getData();
                $em = $this->getDoctrine()->getManager();
                $em->flush();

                $this->addFlash('notice', 'Changes saved for restriction "' . $restriction->getName() . '"');
                return $this->redirectToRoute('user_info_dietary_restriction');
            }

            return $this->render('shared/form/form.html.twig', array(
                'form' => $form->createView()
            ));
        }
    }

    /**
     * @Route("/user/info/dietary_restriction_category/create", name="user_info_dietary_restriction__category_create")
     */
    public function infoDietaryRestrictionCategoryCreateAction(Request $request) {
        $this->denyAccessUnlessGranted(['admin', 'developer', 'user']);

        $category = new DietaryRestrictionCategory();
        $form = $this->createForm(DietaryRestrictionCategoryType::class, $category);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $category = $form->getData();
            $em = $this->getDoctrine()->getManager();
            $em->persist($category);
            $em->flush();

            $this->addFlash('notice', 'Successfully created category "' . $category->getName() . '"');
            unset($category);
            unset($form);
            $category = new DietaryRestrictionCategory();
            $form = $this->createForm(DietaryRestrictionCategoryType::class, $category);
        }

        return $this->render('shared/form/form.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/user/info/dietary_restriction_category/edit/{id}", name="user_info_dietary_restriction_category_edit")
     */
    public function infoDietaryRestrictionCategoryEditAction(Request $request, $id) {
        $this->denyAccessUnlessGranted(['admin', 'developer', 'user']);

        $category = $this->getDoctrine()->getRepository('App\User:Info\DietaryRestrictionCategory')->find($id);

        if (empty($category)) {
            throw $this->createNotFoundException('Category with id ' . $id . ' not found');
        } else {
            $form = $this->createForm(DietaryRestrictionCategoryType::class, $category);

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $category = $form->getData();
                $em = $this->getDoctrine()->getManager();
                $em->flush();

                $this->addFlash('notice', 'Changes saved for category "' . $category->getName() . '"');
                return $this->redirectToRoute('user_info_dietary_restriction');
            }

            return $this->render('shared/form/form.html.twig', array(
                'form' => $form->createView()
            ));
        }
    }
}