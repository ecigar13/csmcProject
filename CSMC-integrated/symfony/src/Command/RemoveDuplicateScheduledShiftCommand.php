<?php

namespace App\Command;

use App\Entity\Schedule\Absence;
use App\Entity\Schedule\ScheduledShift;
use App\Entity\Schedule\Shift;
use App\Entity\Schedule\ShiftAssignment;
use App\Entity\User\Role;
use App\Entity\User\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Ldap\Adapter\ExtLdap\Adapter;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class RemoveDuplicateScheduledShiftCommand extends Command {
    protected static $defaultName = 'app:purge-schedule';

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure() {
        $this->setDescription('Remove duplicate scheduled shifts and assignments');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $io = new SymfonyStyle($input, $output);

        $shifts = $this->entityManager
            ->getRepository(Shift::class)
            ->findAll();

        $io->section('Scheduled Shifts');
        $io->progressStart(count($shifts));
        foreach ($shifts as $shift) {
            $duplicates = array();

            $scheduled_shifts = $this->entityManager
                ->getRepository(ScheduledShift::class)
                ->findByShift($shift);

            foreach ($scheduled_shifts as $ss) {
                $duplicates[$ss->getDate()->format('m/d/y')][] = $ss;
            }

            foreach ($duplicates as $date => $a) {
                if (count($a) > 1) {
                    $io->section($date . ' ' . count($a));
                    $safe = array();
                    $not_safe = array();
                    foreach ($a as $ss) {
                        $is_safe = true;
                        $assignments = $ss->getAssignments();
                        foreach ($assignments as $as) {
                            if ($as->getSession() != null || $as->getAbsence() != null) {
                                $is_safe = false;
                            }

                            if($this->entityManager->getRepository(Absence::class)->findOneBySubstitute($as) != null) {
                                $is_safe = false;
                            }
                        }

                        if (!$is_safe) {
                            $not_safe[] = $ss;
                        } else {
                            $safe[] = $ss;
                        }
                    }

                    if (count($not_safe) == 1) {
                        $io->warning('1 unsafe record found, deleting safe records');
                        $to_delete = count($safe);
                        $io->text('To delete: ' . $to_delete);
                        for ($i = 0; $i < $to_delete; $i++) {
                            $this->entityManager->remove($safe[$i]);
                        }

                        $io->text('Deleted ' . $to_delete);
                    } elseif (count($not_safe) >= 2) {
                        $io->warning(count($not_safe) . ' unsafe records, shift: ' . $shift->getId());
                        $keep = $not_safe[0]; // keep first
                        $keep_assignments = $keep->getAssignments();
                        // each not safe (except first) save data to first
                        for ($i = 1; $i < count($not_safe); $i++) {
                            $assignments = $a[$i]->getAssignments();
                            foreach ($assignments as $as) {
                                if ($as->getSession() != null) {
                                    foreach ($keep_assignments as $assign) {
                                        if ($assign->getMentor()->getId() == $as->getMentor()->getId()) {
                                            $assign->setSession($as->getSession());
                                            $as->setSession(null);
                                        }
                                    }
                                }

                                if ($as->getAbsence() != null) {
                                    foreach ($keep_assignments as $assign) {
                                        if ($assign->getMentor()->getId() == $as->getMentor()->getId()) {
                                            $absence = $as->getAbsence();
                                            $as->setAbsence(null);
                                            $this->entityManager->flush();
                                            $assign->setAbsence($absence);

                                        }
                                    }
                                }
                                
                                $sub = $this->entityManager->getRepository(Absence::class)->findOneBySubstitute($as);
                                if($sub != null) {
                                    foreach($keep_assignments as $assign) {
                                        if($assign->getMentor()->getId() == $as->getMentor()->getId()) {
                                            $sub->setSubstitute($assign);
                                        }
                                    }
                                }
                            }

                            $safe[] = $a[$i];

                            $to_delete = count($safe);
                            $io->text('To delete: ' . $to_delete);
                            for ($i = 0; $i < $to_delete; $i++) {
                                $this->entityManager->remove($safe[$i]);
                            }

                            $io->text('Deleted ' . $to_delete);
                        }
                    } else {
                        $to_delete = count($safe) - 1;
                        $io->text('To delete: ' . $to_delete);
                        for ($i = 0; $i < $to_delete; $i++) {
                            $this->entityManager->remove($safe[$i]);
                        }

                        $io->text('Deleted ' . $to_delete);
                    }
                }
            }

            $io->progressAdvance();
        }
        $io->progressFinish();

        $this->entityManager->flush();

        $shifts = $this->entityManager
            ->getRepository(Shift::class)
            ->findAll();

        $io->section('Shift Assignments');
        foreach ($shifts as $shift) {
            $scheduled_shifts = $this->entityManager
                ->getRepository(ScheduledShift::class)
                ->findByShift($shift);

            foreach ($scheduled_shifts as $ss) {
                $duplicates = array();

                foreach ($ss->getAssignments() as $a1) {
                    $duplicates[$a1->getMentor()->getId()] = array();;
                    $duplicates[$a1->getMentor()->getId()][$a1->getId()] = $a1;
                    foreach ($ss->getAssignments() as $a2) {
                        if ($a1->getMentor()->getId() == $a2->getMentor()->getId()) {
                            $duplicates[$a1->getMentor()
                                ->getId()][$a2->getId()] = $a2;
                        }
                    }
                }

                foreach ($duplicates as $u) {
                    $io->text('Number of users in shift: ' . count($u));
                    if (count($u) > 1) {
                        $safe = array();
                        $unsafe = array();
                        foreach ($u as $a) {
                            $sub = $this->entityManager->getRepository(Absence::class)->findOneBySubstitute($a);

                            if ($a->getSession() != null || $a->getAbsence() != null || $sub != null) {
                                $unsafe[] = $a;
                            } else {
                                $safe[] = $a;
                            }
                        }

                        if (count($unsafe) > 1) {
                            $io->warning('Multiple unsafe, deleting all but 1');
                            $session = null;
                            $absence = null;
                            $sub = null;
                            foreach ($unsafe as $a) {
                                if ($a->getAbsence() != null) {
                                    $absence = $a->getAbsence();
                                }

                                if ($a->getSession() != null) {
                                    $session = $a->getSession();
                                }

                                $subst = $this->entityManager->getRepository(Absence::class)->findOneBySubstitute($a);
                                if($subst != null) {
                                    $sub = $subst;
                                }
                            }

                            $unsafe[0]->setAbsence($absence);
                            // $absence->setSubstitute($unsafe[0]);
                            if($sub != null) {
                                $sub->setSubstitute($unsafe[0]);
                            }
                            $unsafe[0]->setSession($session);

                            $this->entityManager->flush();

                            for ($i = 1; $i < count($unsafe); $i++) {
                                $unsafe[$i]->setSession(null);
                                $ab = $unsafe[$i]->getAbsence();
                                if($ab != null) {
                                    $ab->setSubstitute(null);
                                }
                                $unsafe[$i]->setAbsence(null);
                            }

                            $this->entityManager->flush();

                            for($i = 1; $i < count($unsafe); $i++) {
                                $this->entityManager->remove($unsafe[$i]);
                            }

                            $this->entityManager->flush();

                            foreach ($safe as $a) {
                                $this->entityManager->remove($a);
                            }

                            $this->entityManager->flush();
                        } elseif (count($unsafe) == 1) {
                            // $io->warning('One unsafe, deleting all safe');
                            foreach ($safe as $a) {
                                $this->entityManager->remove($a);
                            }

                            $this->entityManager->flush();
                        } else {
                            // $io->warning('Deleting all but one safe');
                            $io->text('deleting ' . count($safe));
                            for ($i = 1; $i < count($safe); $i++) {
                                $this->entityManager->remove($safe[$i]);
                            }
                            
                            $this->entityManager->flush();
                        }
                    }
                }
            }
        }

        $io->block('Flushing...');

        $this->entityManager->flush();

        $io->success('');
    }
}
