<?php

namespace App\Tests\Form\Data;

use App\Entity\Misc\Subject;
use App\Entity\User\Info\PreferredNameModificationRequest;
use App\Entity\User\Info\Profile;
use App\Entity\User\Info\Specialty;
use App\Entity\User\User;
use App\Form\Data\ProfileFormData;
use App\Tests\Base\PersistenceTest;
use App\Tests\TestUtils\ReflectionUtils;

class ProfileFormDataTest extends PersistenceTest
{
    /**
     * @var Subject[]
     */
    private $subjects;

    /**
     * @var User
     */
    private $user;

    /**
     * This tests that the preferred name corresponds to the profile's request if it exists or just the value of the profile
     * if there is no request.
     *
     * @dataProvider createPreferredNameOnCreationData
     * @param Profile $profile
     * @param string $expectedPreferredName
     */
    public function testPreferredNameOnCreation(Profile $profile, string $expectedPreferredName=null)
    {
        self::assertEquals($expectedPreferredName, (ProfileFormData::createFromProfile($profile, $this->entityManager))->getPreferredName());
    }

    public function createPreferredNameOnCreationData()
    {
        $profile1 = (new User('u1', 'u1', 'txt000000'))->getProfile();

        // Use reflection to directly set the name
        $profile2 = (new User('u2', 'u2', 'txt000001'))->getProfile();
        ReflectionUtils::assignValueToPrivateProperty($profile2, 'preferredName', 'Preferred Name');

        // Use reflection to add a new request
        $profile3 = (new User('u3', 'u3', 'txt000002'))->getProfile();
        $requests = ReflectionUtils::extractPrivatePropertyValue($profile3, 'modificationRequests');
        $newRequest = new PreferredNameModificationRequest($profile3);
        $newRequest->update('Preferred Name Request');
        $requests[] = $newRequest;

        return array(
            [$profile1, null],
            [$profile2, 'Preferred Name'],
            [$profile3, 'Preferred Name Request']
        );
    }

    /**
     * Test whether all the subjects in the database are returned as part of the form even when the profile initially
     * has no specialties defined.
     */
    public function testSpecialtiesInFormCreation()
    {
        // Make sure the user has no specialties
        if ($this->entityManager->getRepository(Specialty::class)
                ->count(array('profile' => $this->user->getProfile())) != 0) {
            throw new \LogicException('The user should not have any specialties in the database');
        }

        $form = ProfileFormData::createFromProfile($this->user->getProfile(), $this->entityManager);

        $formSubjectNames = array_map(function ($specialtyForm) {
            return $specialtyForm->getTopic()->getId();
        }, $form->getSpecialties());

        $allSubjectNames = array_map(function ($subject) {
            return $subject->getId();
        }, $this->entityManager->getRepository(Subject::class)->findAll());

        self::assertEquals($allSubjectNames, $formSubjectNames, 'Database specialties and form specialties are not the same',
            0.0, 1, true);
    }

    /**
     * @inheritdoc
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
