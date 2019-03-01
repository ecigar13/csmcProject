<?php


namespace App\Controller;

use App\Entity\Occurrence\BehaviorOccurrence;
use App\Entity\Occurrence\OccurrenceType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User\User;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class BehaviorOccurrenceSubmissionController extends Controller
{
    /**
     * @Route(path="/submit_behavior_occurrence", name="behavior_occurrence_submission")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function submitBehavorOccurrence(Request $request)
    {
        $session = $this->get('session');
        /** @var User $theUser */
        $theUser = $this->getUser();
        $form = $this->createFormBuilder()
            ->add("subject", ChoiceType::class, array (
                "required" => true,
                "mapped" => true,
                "placeholder" => "Select One",
                "choices" => $this->fillUsernames($theUser->getUsername())
            ))
            ->add("type", ChoiceType::class, array (
                "required" => true,
                "placeholder" => "Select One",
                "choices" => $this->fillTypes()
            ))
            ->add("details", TextareaType::class, array(
                "required" => false,
            ))
            ->add("dateOfOccurrence", TextType::class, array(
                "required" => true,
                'attr' => array(
                    'readonly' => true,
                )
            ))
            ->add("anonymous", CheckboxType::class, array(
                "required" => false
            ))
            ->add("submit", SubmitType::class)
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $formData = $form->getData();
            $occurrenceDetails = "None";
            if ($formData["details"]) {
                $occurrenceDetails = $formData["details"];
            }
            $postObject = new BehaviorOccurrence($formData["subject"],
                $formData["type"]->getDescription(),
                $occurrenceDetails,
                new \DateTime($formData["dateOfOccurrence"]),
                ($formData["anonymous"] ? null : $theUser));
            $postObject->setPoints($formData["type"]->getDefaultPoints());
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($postObject);
            $entityManager->flush();
            $session->getFlashBag()->add('success', 'Thank you for your submission! Your response was recorded successfully.');
            if ($this->isGranted('admin'))
                return $this->redirectToRoute('admin_home');
            return $this->redirectToRoute('home');
        }

        $displayName = $theUser->getFirstName();
        $userProfile = $theUser->getProfile();
        if ($userProfile && $userProfile->getPreferredName()) {
            $displayName = $userProfile->getPreferredName();
        }
        return $this->render('role/mentor/occurrence_submission.twig', array(
            'displayName' => $displayName,
            'form' => $form->createView()
        ));
    }

    private function fillUsernames(string $excludedUserName) {
        $choices = [];
        foreach ($this->getDoctrine()->getRepository(User::class)->findAll() as $eachUser) {
            if ($eachUser->hasRole("mentor") && $eachUser->getUsername() != $excludedUserName) {
                $displayName = $eachUser->getFirstName();
                if ($eachUser->getProfile() && $eachUser->getProfile()->getPreferredName()) {
                    $displayName = $eachUser->getProfile()->getPreferredName();
                }
                $choices[$displayName] = $eachUser;
            }
        }
        return $choices;
    }

    private function fillTypes() {
        $choices = [];
        foreach ($this->getDoctrine()->getRepository(OccurrenceType::class)->findAll() as $eachType) {
            $choices[$eachType->getDescription()] = $eachType;
        }
        return $choices;
    }

}