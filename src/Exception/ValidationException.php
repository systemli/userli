<?php

namespace App\Exception;

use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Class ValidationException.
 */
class ValidationException extends \Exception
{
    /**
     * @var ConstraintViolationListInterface
     */
    private $constraints;

    /**
     * @param ConstraintViolationListInterface $constraints
     */
    public function __construct(ConstraintViolationListInterface $constraints)
    {
        $this->constraints = $constraints;

        $messages = [];
        foreach ($constraints as $constraint) {
            /** @var ConstraintViolationInterface $constraint */
            $message = $constraint->getMessage();

            if (!empty($constraint->getPropertyPath()) && is_string($constraint->getInvalidValue())) {
                $message = sprintf('%s [%s => %s]', $message, $constraint->getPropertyPath(), $constraint->getInvalidValue());
            }

            $messages[] = $message;
        }
        $message = implode(PHP_EOL, $messages);

        parent::__construct($message);
    }

    /**
     * @return ConstraintViolationListInterface
     */
    public function getConstraints()
    {
        return $this->constraints;
    }
}
