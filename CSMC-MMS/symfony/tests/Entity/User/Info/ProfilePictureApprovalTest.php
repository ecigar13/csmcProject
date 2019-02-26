<?php


namespace App\Tests\Entity\User\Info;


use App\DataTransferObject\FileData;
use App\Entity\File\File;
use App\Entity\User\Info\Profile;
use App\Entity\User\Info\ProfilePictureModificationRequest;
use App\Form\Data\ProfileFormData;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProfilePictureApprovalTest extends AdminApprovalTest
{
    /**
     * We don't allow setting the picture to null, so ignore this test.
     */
    public function testRevertValue()
    {
        // So that it's not flagged as risky
        self::assertTrue(true);
    }

    /**
     * @inheritdoc
     */
    protected function checkInitiallyNull(Profile $profile)
    {
        if ($profile->getProfilePicture() != null) {
            throw new \LogicException('Profile picture must be initially null');
        }
    }

    /**
     * @inheritdoc
     */
    protected function getRequestType()
    {
        return ProfilePictureModificationRequest::class;
    }

    /**
     * @inheritdoc
     * @throws \Doctrine\ORM\ORMException
     */
    protected function createNewRequest(Profile $profile, $value = null)
    {
        // Must persist picture first
        $this->entityManager->persist($value);

        // This method should create the approval request
        $profile->updateProfilePicture($value);
    }

    /**
     * @inheritdoc
     */
    protected function getTestValues()
    {
        return array_map(
            function ($name) {
                return $this->loadPicture($name);
            },
            array('image-1.png', 'image-2.png', 'image-3.png'));
    }

    /**
     * Returns a @see File instance corresponding to an image in the test data folder.
     *
     * @param string $name
     * @return File
     */
    private function loadPicture(string $name)
    {
        // We must create a copy because the file gets removed after the test
        // Add some prefix to the name to avoid overwriting existing data in the temp folder
        $testFilePath = sys_get_temp_dir() . '/MMS-testing-data-' . $name;
        copy('tests/Data/' . $name, $testFilePath);

        $image = new UploadedFile($testFilePath, $name, null, null, null, true);

        $fileData = new FileData();
        $fileData->user = null;
        $fileData->file = $image;

        $file = File::fromUploadData($fileData, $this->entityManager, []);

        return $file;
    }

    /**
     * @inheritdoc
     */
    protected function updateDatabaseValue(Profile $profile, $image)
    {
        // Must persist the image first
        $this->entityManager->persist($image);

        $this->entityManager->getRepository(Profile::class)
            ->createQueryBuilder('p')
            ->update('App:User\Info\Profile', 'p')
            ->set('p.image', '?1')
            ->setParameter(1, $image->getId())
            ->where('p.user = ?2')
            ->setParameter(2, $profile->getUser())
            ->getQuery()
            ->execute();
    }

    /**
     * @inheritdoc
     */
    protected function checkInitiallyNotNull(Profile $nonEmptyProfile)
    {
        if ($nonEmptyProfile->getProfilePicture() == null) {
            throw new \LogicException('Profile picture must be initially not null');
        }
    }

    /**
     * @inheritdoc
     */
    protected function extractCurrentValue(Profile $profile)
    {
        return $profile->getProfilePicture();
    }

    /**
     * @inheritdoc
     */
    protected function setWithAdminOverride(Profile $profile, $value)
    {
        $profile->updateProfilePicture($value, true);
    }
}