<?php


namespace App\Tests\Entity\User\Info;


use App\Entity\User\Info\Profile;
use App\Entity\User\Info\ProfileModificationRequest;
use App\Entity\User\User;
use App\Tests\Base\PersistenceTest;

/**
 * Base class for testing admin approval on a particular field of the user profile.
 *
 * @package App\Tests\Entity\User\Info
 */
abstract class AdminApprovalTest extends PersistenceTest
{
    /**
     * @var Profile
     */
    private $emptyProfile;

    /**
     * @var Profile
     */
    private $nonEmptyProfile;

    /**
     * Tests the creation of a modification request when the value is initially `null`.
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testCreateRequest()
    {
        $this->checkInitiallyNull($this->emptyProfile);

        $newValue = $this->getTestValues()[1];

        $this->processNewRequestCreation($this->emptyProfile, $newValue);

        $this->assertCorrectRequest($this->emptyProfile, $newValue);
    }

    /**
     * Tests the case of a user changing the value when a request already exists.
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testUpdateRequest()
    {
        $this->checkInitiallyNull($this->emptyProfile);

        $originalRequestValue = $this->getTestValues()[1];
        $this->processNewRequestCreation($this->emptyProfile, $originalRequestValue);

        // Change the tested value again
        $updatedRequestValue = $this->getTestValues()[2];
        $this->processNewRequestCreation($this->emptyProfile, $updatedRequestValue);

        $this->assertCorrectRequest($this->emptyProfile, $updatedRequestValue);
    }

    /**
     * Tests the case of a user having a non-null value for the field, then creating a change request, then cancelling it by
     * setting it to the same it is currently.
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testDeleteRequest()
    {
        $this->checkInitiallyNotNull($this->nonEmptyProfile);

        // User creates a request for some other value
        $this->processNewRequestCreation($this->nonEmptyProfile, $this->getTestValues()[1]);

        // Then changes it to what it currently is
        $currentValue = $this->extractCurrentValue($this->nonEmptyProfile);
        $this->processNewRequestCreation($this->nonEmptyProfile, $currentValue);

        $requestCount = count($this->entityManager->getRepository($this->getRequestType())
            ->findAll());
        self::assertEquals(0, $requestCount,
            'There should be no modification requests after setting the value to the current value');
    }

    /**
     * Tests reverting the value back to null without needing an approval.
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testRevertValue()
    {
        $this->checkInitiallyNotNull($this->nonEmptyProfile);

        $this->processNewRequestCreation($this->nonEmptyProfile, null);

        $profile = $this->reloadProfile($this->nonEmptyProfile);

        self::assertNull($this->extractCurrentValue($profile), 'The value should be null without requiring approval');
    }

    /**
     * Tests setting the field value and approving the request when it is initially `null`.
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function testApproveRequest()
    {
        $profile = $this->emptyProfile;
        $this->checkInitiallyNull($profile);

        $newValue = $this->getTestValues()[1];
        $actionName = 'approve';

        $this->processRequest($profile, $actionName, $newValue, $newValue);
    }

    /**
     * Tests a request being rejected when the value is initially null.
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testRejectInitialRequest()
    {
        $profile = $this->emptyProfile;

        $this->checkInitiallyNull($profile);

        $this->processRequest($profile, 'reject', $this->getTestValues()[2], null);
    }

    /**
     * Rejects a request when the user already has a value set.
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testRejectRequest()
    {
        $profile = $this->nonEmptyProfile;

        $this->checkInitiallyNotNull($profile);

        $this->processRequest($profile, 'reject', $this->getTestValues()[1], $this->extractCurrentValue($profile));
    }

    /**
     * Tests the functionality that directly sets a profile value without creating a request.
     */
    public function testAdminOverride()
    {
        $profile = $this->emptyProfile;

        $this->checkInitiallyNull($profile);

        $value = $this->getTestValues()[0];
        $this->setWithAdminOverride($profile, $value);

        self::assertEquals($this->extractCurrentValue($profile), $value);

        $requestCount = count($this->entityManager->getRepository($this->getRequestType())
            ->findAll());
        self::assertEquals(0, $requestCount,
            'There should be no modification requests after setting the value via admin override');
    }

    /**
     * Creates a request and then approves or denies it according to the @see actionName parameter. Then asserts whether
     * the profile has the correct expected value.
     *
     * @param $profile
     * @param $actionName
     * @param $newValue
     * @param $expectedValue
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function processRequest($profile, $actionName, $newValue, $expectedValue = null)
    {
        if ($actionName != 'approve' && $actionName != 'reject') {
            throw new \LogicException('Action name must be "approve" or "reject"');
        }

        $this->processNewRequestCreation($profile, $newValue);

        // Get the request and apply the action to it
        $request = $this->entityManager->getRepository($this->getRequestType())->findAll()[0];
        // Approve or reject
        $request->{$actionName}();
        $this->entityManager->flush();

        self::assertEquals($expectedValue, $this->extractCurrentValue($profile), 'The field value is incorrect in the database');

        self::assertEquals(0,
            count($this->entityManager->getRepository($this->getRequestType())->findAll()),
            'The modification request must be removed after being processed');
    }

    /**
     * Asserts that only one request exists, that it corresponds to the correct profile and that it contains the
     * correct value.
     *
     * @param Profile $profile
     * @param $newValue mixed the value corresponding to the modification request.
     */
    private function assertCorrectRequest(Profile $profile, $newValue)
    {
        // Get all update requests of the tested type
        $requests = $this->entityManager->getRepository($this->getRequestType())->findAll();

        self::assertEquals(1, count($requests),
            "Exactly one request should be created when updating a field that needs approval");

        /** @var ProfileModificationRequest $request */
        $request = $requests[0];

        self::assertEquals($profile, $request->getProfile(),
            "The new request should point to the correct profile");
        self::assertEquals($newValue, $request->getValue(),
            "The new request should contain the correct new value");
    }

    /**
     * Retrieves the profile instance from the database.
     *
     * @param Profile $profile
     * @return Profile
     */
    private function reloadProfile(Profile $profile)
    {
        return $this->entityManager->getRepository(Profile::class)
            ->findOneBy(array('user' => $profile->getUser()));
    }

    /**
     * @param Profile $profile
     * @param null $value
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function processNewRequestCreation(Profile $profile, $value = null)
    {
        $this->createNewRequest($profile, $value);

        $this->entityManager->persist($profile);
        $this->entityManager->flush();
    }

    /**
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function createTestData()
    {
        $user1 = new User('Test', 'User', 'txu000000');
        $this->emptyProfile = $user1->getProfile();
        $this->entityManager->persist($user1);

        $user2 = new User('Other', 'Name', 'oxn000000');
        $this->nonEmptyProfile = $user2->getProfile();
        $this->entityManager->persist($user2);

        // Must flush here so that the entity is actually in the database for the next step
        $this->entityManager->flush();

        $this->updateDatabaseValue($this->nonEmptyProfile, $this->getTestValues()[0]);
        $this->entityManager->flush();
        // Must clear it because we updated the database directly, otherwise it will not show the changes
        $this->entityManager->clear();

        $this->emptyProfile = $this->reloadProfile($this->emptyProfile);
        $this->nonEmptyProfile = $this->reloadProfile($this->nonEmptyProfile);
    }

    /**
     * Must throw an exception if the tested field is not initially null in the given profile instance.
     *
     * @param Profile $profile
     */
    protected abstract function checkInitiallyNull(Profile $profile);

    /**
     * Must throw an exception if the tested field is initially null.
     *
     * @param Profile $nonEmptyProfile
     */
    protected abstract function checkInitiallyNotNull(Profile $nonEmptyProfile);

    /**
     * Returns the current value of the field under test.
     *
     * @param Profile $profile
     * @return mixed
     */
    protected abstract function extractCurrentValue(Profile $profile);

    /**
     * Calls the method or methods that will cause the profile instance to create a new modification request
     * for the field under test. There is no need to persist the profile.
     *
     * @param Profile $profile
     * @param $value mixed
     */
    protected abstract function createNewRequest(Profile $profile, $value = null);

    /**
     * Sets the value of the field under test in the database directly, without going through the authorization process.
     * Used to initialize test scenarios (e.g. profile already has a preferred name set).
     *
     * @param Profile $profile
     * @param $value
     */
    protected abstract function updateDatabaseValue(Profile $profile, $value);

    /**
     * Returns an array of three valid values that will be used as test data. The elements must be of the same type as
     * the field under test.
     *
     * @return array
     */
    protected abstract function getTestValues();

    /**
     * Returns the type of the request corresponding to the field under test.
     *
     * @return string
     */
    protected abstract function getRequestType();

    /**
     * Sets the tested value directly using the admin override functionality.
     *
     * @param Profile $profile
     * @param $value
     */
    protected abstract function setWithAdminOverride(Profile $profile, $value);
}