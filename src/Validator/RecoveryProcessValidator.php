<?php

namespace App\Validator;

use App\Form\Model\RecoveryProcess;
use App\Handler\RecoveryTokenHandler;
use App\Repository\UserRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * @author doobry <doobry@systemli.org>
 */
class RecoveryProcessValidator extends ConstraintValidator
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var RecoveryTokenHandler
     */
    private $handler;

    /**
     * RecoveryProcessValidator constructor.
     *
     * @param ObjectManager        $manager
     * @param RecoveryTokenHandler $handler
     */
    public function __construct(ObjectManager $manager, RecoveryTokenHandler $handler)
    {
        $this->userRepository = $manager->getRepository('App:User');
        $this->handler = $handler;
    }

    /**
     * Checks if the passed value is valid.
     *
     * @param RecoveryProcess $value
     * @param Constraint      $constraint
     *
     * @throws UnexpectedTypeException
     *
     * @return bool
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof RecoveryProcess) {
            throw new UnexpectedTypeException('Wrong value type given', 'App\Form\Model\RecoveryProcess');
        }

        $user = $this->userRepository->findByEmail($value->username);

        if (null === $user) {
            $this->context->addViolation('form.recovery-token-invalid');

            return false;
        } elseif (!$this->handler->verify($user, $value->recoveryToken)) {
            $this->context->addViolation('form.recovery-token-invalid');

            return false;
        }

        return true;
    }
}
