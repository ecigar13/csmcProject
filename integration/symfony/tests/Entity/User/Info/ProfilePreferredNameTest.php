<?php

namespace App\Entity\User\Info;


use App\Form\Data\ProfileFormData;
use App\Tests\Entity\User\Info\AdminApprovalTest;

class ProfilePreferredNameTest extends AdminApprovalTest
{
    /**
     * @inheritdoc
     */
    protected function checkInitiallyNull(Profile $profile)
    {
        if ($profile->getPreferredName() != null) {
            throw new \LogicException("Preferred name must initially be null");
        }
    }

    /**
     * @inheritdoc
     */
    protected function getRequestType()
    {
        return PreferredNameModificationRequest::class;
    }

    /**
     * @inheritdoc
     */
    protected function createNewRequest(Profile $profile, $value = null)
    {
        // Form submitted by the user with a new preferred name
        $form = ProfileFormData::createFromProfile($profile, $this->entityManager);
        $form->setPreferredName($value);

        // Update profile with the form
        $profile->updateFromFormData($form);
    }

    /**
     * @inheritdoc
     */
    protected function getTestValues()
    {
        return array('New Name', 'Updated Name', 'Another Name');
    }

    /**
     * @inheritdoc
     */
    protected function updateDatabaseValue(Profile $profile, $value)
    {
        $this->entityManager->getRepository(Profile::class)
            ->createQueryBuilder('p')
            ->update('App:User\Info\Profile', 'p')
            ->set('p.preferredName', '?1')
            ->setParameter(1, $value)
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
        if ($nonEmptyProfile->getPreferredName() == null) {
            throw new \LogicException('The preferred name must be initially not null');
        }
    }

    /**
     * @inheritdoc
     */
    protected function extractCurrentValue(Profile $profile)
    {
        return $profile->getPreferredName();
    }

    /**
     * @inheritdoc
     */
    protected function setWithAdminOverride(Profile $profile, $value)
    {
        $form = ProfileFormData::createFromProfile($profile, $this->entityManager);

        $form->setPreferredName($value);

        $profile->updateFromFormData($form, true);
    }
}
