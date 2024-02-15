<?php

namespace App\Exception;

use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Exception à émettre losque l'entité n'est pas trouvée en base
 */
class ValidationException extends \Exception
{
    public function __construct(ConstraintViolationListInterface $validationErrors)
    {
        // Formatage du message (passage en json)
        $message = [];
        $message["message"] = "Données invalides : ";

        $violations = [];
        foreach ($validationErrors as $violation) {
            $violations[] = [
                'property' => $violation->getPropertyPath(),
                'message' => $this->prepareMessage($violation),
                // 'constraint' => $violation->getConstraint(),
                // 'code' => $violation->getCode(),
                'errorName' => $violation->getConstraint()->getErrorName($violation->getCode())
            ];

            $message["message"] .= $violation->getPropertyPath() . ", ";
        }

        $message["violations"] = $violations;

        parent::__construct(json_encode($message));
    }

    /**
     * Retourne un meesage en fontion de la violation
     * @param ConstraintViolation violation de la contrainte
     * @return string message d'erreur
     */
    public function prepareMessage(ConstraintViolation $violation): string
    {
        $message = '';

        $errorName = $violation->getConstraint()->getErrorName($violation->getCode());
        switch ($errorName) {
            case 'TOO_LONG_ERROR':
                $message = "La valeur fournie est trop longue. Il devrait y avoir " . $violation->getConstraint()->max . " caractères maximum.";
                break;

            case 'NOT_UNIQUE_ERROR':
                $message = "La valeur fournie est déjà utilisée. Elle doit être unique";
                break;

            case 'IS_BLANK_ERROR':
                $message = "La valeur fournie ne devrait pas être vide.";
                break;

            case 'NOT_BLANK_ERROR':
                $message = "La valeur fournie devrait être vide.";
                break;

            case 'IS_NULL_ERROR':
                $message = "La valeur fournie ne devrait pas être nulle.";
                break;

            case 'INVALID_TYPE_ERROR':
                $message = "La valeur fournie devrait être du type \"" . $violation->getConstraint()->type . "\".";
                break;

            default:
                $message = $violation->getConstraint()->message;
                break;
        }

        return $message;
    }
}
