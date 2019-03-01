<?php

namespace App\Security\Voter;

use App\Entity\User\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ViewVoter extends Voter {
    const STUDENT = 'student';
    const MENTOR = 'mentor';
    const SHIFT_LEADER = 'shift_leader';
    const TEACHING_ASSISTANT = 'teaching_assistant';
    const INSTRUCTOR = 'instructor';
    const ADMIN = 'admin';
    const DEVELOPER = 'developer';

    /**
     *
     * {@inheritDoc}
     * @see \Symfony\Component\Security\Core\Authorization\Voter\Voter::supports()
     */
    protected function supports($attribute, $subject) {
        return in_array($attribute, array(
            self::STUDENT,
            self::MENTOR,
            self::INSTRUCTOR,
            self::ADMIN,
            self::DEVELOPER
        ));
    }

    /**
     *
     * {@inheritDoc}
     * @see \Symfony\Component\Security\Core\Authorization\Voter\Voter::voteOnAttribute()
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token) {
        $user = $token->getUser();

        // user must be logged in
        if (!$user instanceof User) {
            return false;
        }

        return $this->canView($subject, $user, $attribute);
    }

    protected function canView($subject, $user, $attribute) {
        return $user->hasRole($attribute);
    }
}
