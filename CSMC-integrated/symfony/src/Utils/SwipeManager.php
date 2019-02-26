<?php


namespace App\Utils;


use App\Entity\Course\Course;
use App\Entity\Course\Section;
use App\Entity\Schedule\Timesheet;
use App\Entity\Session\Attendance;
use App\Entity\Session\Quiz;
use App\Entity\Session\QuizAttendance;
use App\Entity\Session\SessionTimeSlot;
use App\Entity\Session\WalkInActivity;
use App\Entity\Session\WalkInAttendance;
use App\Entity\User\Role;
use App\Entity\User\User;
use App\Repository\Course\SectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Ldap\Adapter\ExtLdap\Adapter;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Symfony\Component\Ldap\Ldap;

class SwipeManager {
    const SUCCESS_ATTENDEE_IN = 'attendee_in';
    const SUCCESS_ATTENDEE_OUT = 'attendee_out';
    const SUCCESS_SESSION_START = 'session_start';
    const SUCCESS_SESSION_END = 'session_end';
    const SUCCESS_MENTOR_IN = 'mentor_in';
    const SUCCESS_MENTOR_OUT = 'mentor_out';
    const SUCCESS_ENTRANCE = 'entrance';
    const SUCCESS_EXIT = 'exit';

    const ERROR_SESSION_ENDED = 'session_already_ended';
    const ERROR_UNREGISTERED = 'unregistered_user';
    const ERROR_SESSION_NOT_STARTED = 'session_needs_starting';
    const ERROR_INELIGIBLE = 'ineligible';
    const ERROR_BAD_CREDENTIALS = 'bad_credentials';
    const ERROR_NO_USER = 'no_user';

    private $entityManager;
    private $logger;

    /**
     * @param string $scancode
     *
     * @return boolean
     */
    public static function isScancodeLegacy($scancode) {
        // compare to 1 because preg_match returns 1, 0, or false, consider it legacy if 1 returns
        return preg_match('/603[0-9]{13}/', $scancode, $matches) == 1;
    }

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    private function success(string $success, $data = array()) {
        $payload = array_merge($data, array(
            'message' => $success
        ));

        return new JsonResponse($payload, 200);
    }

    private function error(string $error) {
        return new JsonResponse($error, 400);
    }

    public function walkInSwipe(string $swipe) {
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findByCardId($swipe);

        if ($user == null) {
            return $this->error(self::ERROR_UNREGISTERED);
        }

        // if mentor
        if ($user->hasRole('mentor')) {
            $timesheet = $this->entityManager
                ->getRepository(Timesheet::class)
                ->findOnDuty($user);

            // if signed in
            if ($timesheet !== null) {
                $timesheet->signOut();
                $this->entityManager->flush();

                return $this->success(self::SUCCESS_MENTOR_OUT, array(
                    'user' => $user->getPreferredName()
                ));
            }

            // if not
            $timesheet = new Timesheet($user);
            $this->entityManager->persist($timesheet);
            $this->entityManager->flush();

            return $this->success(self::SUCCESS_MENTOR_IN, array(
                'user' => $user->getPreferredName()
            ));
        }

        // if student
        $attendance = $this->entityManager
            ->getRepository(WalkInAttendance::class)
            ->findCurrent($user);

        // if signed in
        if ($attendance) {
            return $this->success(self::SUCCESS_EXIT, array(
                'user' => $user->getId(),
                'mentors' => $this->getCurrentMentors()
            ));
        }

        $attendance = $this->entityManager
            ->getRepository(QuizAttendance::class)
            ->findCurrent($user);

        if ($attendance) {
            return $this->success(self::SUCCESS_EXIT, array(
                'user' => $user->getId(),
                'mentors' => $this->getCurrentMentors()
            ));
        }

        // if not
        return $this->success(self::SUCCESS_ENTRANCE, array(
            'user' => $user->getId(),
            'courses' => $this->getCoursesFor($user)
        ));
    }

    public function entry(User $user, string $topic, WalkInActivity $activity, Course $course, Quiz $quiz = null) {
        // TODO find section they're in
        // $section = $this->entityManager
        //     ->getRepository(Section::class)
        //     ->findByStudentAndCourse($user, $course);

        $quiz_activity = $this->entityManager
            ->getRepository(WalkInActivity::class)
            ->findByName('Take a Quiz');
        if ($activity->getName() == 'Take a Quiz') {
            $attendance = new QuizAttendance($user, $quiz->getTimeSlot());
        } else {
            $attendance = new WalkInAttendance($user, $activity, $topic, $course);
        }

        $this->entityManager->persist($attendance);
        $this->entityManager->flush();


        return $this->success(self::SUCCESS_ATTENDEE_IN, array(
            'user' => $user->getPreferredName(),
        ));
    }

    public function exit(User $user, array $mentors, string $feedback = null) {
        $attendance = $this->entityManager
            ->getRepository(Attendance::class)
            ->findCurrent($user);

        $attendance->checkOut($mentors, null, $feedback);

        $this->entityManager->flush();

        return $this->success(self::SUCCESS_ATTENDEE_OUT, array(
            'user' => $user->getPreferredName()
        ));
    }

    // TODO handle case if student not in section related to session
    public function sessionSwipe(SessionTimeSlot $session, string $swipe) {
        if ($session->hasEnded()) {
            return $this->error(self::ERROR_SESSION_ENDED);
        }

        $user = $this->entityManager
            ->getRepository(User::class)
            ->findByCardId($swipe);

        if ($user == null) {
            return $this->error(self::ERROR_UNREGISTERED);
        }

        if ($user->hasRole('mentor')) {
            if ($session->hasStarted()) {
                $session->end($user);
                $this->entityManager->flush();
                return $this->success(self::SUCCESS_SESSION_END);
            }

            $session->start($user);
            $this->entityManager->flush();
            return $this->success(self::SUCCESS_SESSION_START);
        }

        if (!$session->hasStarted()) {
            return $this->error(self::ERROR_SESSION_NOT_STARTED);
        }

        // TODO reconsider if they are swiped in and out or just in
        if (!$session->hasAttended($user)) {
            if (!$session->eligible($user)) {
                return $this->error(self::ERROR_INELIGIBLE);
            }

            $attendance = $session->checkIn($user);
            $this->entityManager->flush();

            return $this->success(self::SUCCESS_ATTENDEE_IN, array(
                'user_id' => $user->getId(),
                'user_name' => $user->getFirstName(),
                'time_in' => $attendance->getTimeIn()->format('g:i A')
            ));
        }

        $attendance = $session->checkOut($user, null ,$session->getMentors()->toArray());
        $this->entityManager->flush();

        return $this->success(self::SUCCESS_ATTENDEE_OUT, array(
            'user_id' => $user->getId(),
            'user_name' => $user->getFirstName(),
            'time_out' => $attendance->getTimeOut()->format('g:i A')
        ));
    }

    public function sessionLogIn(SessionTimeSlot $session, string $username, string $password) {
        if ($session->hasEnded()) {
            return $this->error(self::ERROR_SESSION_ENDED);
        }

        $user = $this->ldapQuery($username, $password);

        if ($user === false) {
            return $this->error(self::ERROR_BAD_CREDENTIALS);
        } elseif ($user === null) {
            return $this->error(self::ERROR_NO_USER);
        }

        $attendance = $session->checkIn($user);
        $this->entityManager->flush();

        return $this->success(self::SUCCESS_ATTENDEE_IN, array(
            'user_id' => $user->getId(),
            'user_name' => $user->getName(),
            'time_in' => $attendance->getTimeIn()
        ));
    }

    public function register(string $username, string $password, string $swipe, bool $create = false) {
        $user = $this->ldapQuery($username, $password, $create);

        if ($user === false) {
            return false;
        } elseif ($user === null) {
            return null;
        }

        $user->updateCardId($swipe, self::isScancodeLegacy($swipe));
        $this->entityManager->flush();

        return true;
    }

    private function ldapQuery(string $username, string $password, bool $create = false) {
        $adapter = new Adapter(array(
            'host' => 'nsldap.utdallas.edu',
            'port' => 389,
            'version' => 3,
            'referrals' => false
        ));

        $ldap = new Ldap($adapter);

        $ldap->bind();
        $q = $ldap->query('ou=people,dc=utdallas,dc=edu', '(uid=' . $username . ')')->execute();

        if (!$q->offsetExists(0)) {
            return false; //return new JsonResponse('bad_credentials', 400);
        }

        $entry = $q->offsetGet(0);
        try {
            $ldap->bind($entry->getDn(), $password);
        } catch (ConnectionException $connectionException) {
            return false; // new JsonResponse('bad_credentials', 400);
        }

        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneByUsername($username);

        if ($user === null && $create) {
            $first_name = $entry->getAttribute('givenName')[0];
            $last_name = $entry->getAttribute('sn')[0];

            $user = new User($first_name, $last_name, $username);

            $role = $this->entityManager
                ->getRepository(Role::class)
                ->findOneByName('student');
            $user->addRole($role);

            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        return $user;
    }

    private function getCoursesFor(User $user) {
        return null;
    }

    private function getCurrentMentors() {
        return null;
    }
}