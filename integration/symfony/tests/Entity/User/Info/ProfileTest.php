<?php

namespace App\Tests\Entity\User\Info;

use App\Entity\Misc\Subject;
use App\Entity\User\Info\Specialty;
use App\Entity\User\User;
use App\Form\Data\ProfileFormData;
use App\Form\Data\SpecialtyFormData;
use App\Tests\Base\PersistenceTest;
use App\Tests\TestUtils\ReflectionUtils;

class ProfileTest extends PersistenceTest
{
    /**
     * @var User
     */
    private $user;

    /**
     * @var Subject[]
     */
    private $subjects;

    /**
     * Simulates the case in which a set of specialties that did not exist for the user are submitted with the form.
     *
     * @dataProvider addNewSpecialtiesDataProvider
     * @param int $amountOfNewSubjects
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testAddNewSpecialties(int $amountOfNewSubjects)
    {
        // Take only a subset of the new subjects
        $newlyRatedSubjects = array_slice($this->subjects, 0, $amountOfNewSubjects);

        $form = ProfileFormData::createFromProfile($this->user->getProfile(), $this->entityManager);
        // The form will have all the specialties, so clear them
        ReflectionUtils::assignValueToPrivateProperty($form, 'specialties', array());

        // Create a new specialty form for each newly rated subject
        $form->setSpecialties(array_map(function ($specialty) {
            return SpecialtyFormData::createFromSpecialty(new Specialty($this->user->getProfile(), $specialty));
        }, $newlyRatedSubjects));

        // Update the profile with the newly created specialties
        $this->user->getProfile()->updateFromFormData($form);
        $this->entityManager->persist($this->user);
        $this->entityManager->flush();

        // Retrieve the specialties from the database. They must correspond to the user and the subjects that were rated
        $newSpecialties = $this->entityManager->getRepository(Specialty::class)
            ->findBy(array(
                'profile' => $this->user->getProfile(),
                'topic' => $newlyRatedSubjects
            ));

        // Get only the IDs of the newly rated subjects
        $newlyRatedSubjectsIds = array_map(function ($subject) {
            /** @var Subject $subject */
            return $subject->getId();
        }, $newlyRatedSubjects);

        // Get the subject IDs through the newly created specialties
        $newSpecialtiesSubjectIds = array_map(function ($specialty) {
            /** @var Specialty $specialty */
            return $specialty->getSubject()->getId();
        }, $newSpecialties);

        // Canonicalize (last parameter) sorts the arrays for us
        self::assertEquals($newlyRatedSubjectsIds, $newSpecialtiesSubjectIds, 'Specialties stored in the database do 
        not exactly correspond to the ones submitted in the form', 0.0, 1, true);
    }

    /**
     * The maximum amount of cases here has to agree with the amount of items in the field @see $subjects as
     * initialized in the @see setUp method. I couldn't find any other way of getting it to work because this method
     * gets run before both @see setUp and @see setUpBeforeClass.
     *
     * @return array
     */
    public function addNewSpecialtiesDataProvider()
    {
        return array(
            array(1),
            array(2),
            array(3),
            array(4)
        );
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     */
    protected function createTestData()
    {
        $this->user = new User('Test', 'User', 'txt000000');
        $this->entityManager->persist($this->user);

        $this->subjects = array(
            new Subject('Sub1', 'S1'),
            new Subject('Sub2', 'S2'),
            new Subject('Sub3', 'S3'),
            new Subject('Sub4', 'S4')
        );
        foreach ($this->subjects as $s) {
            $this->entityManager->persist($s);
        }
    }
}
