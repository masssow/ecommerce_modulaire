<?php

namespace App\Service;

use App\Entity\User;
use App\Exception\UserNotFoundException;
use App\Exception\ValidationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Gère la consultation et la mise à jour d’un profil utilisateur.
 */
class UserProfileService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ValidatorInterface     $validator,
    ) {}

    /* -----------------------------------------------------------------
       Lire un profil
       ----------------------------------------------------------------- */

    /**
     * Retourne l’entité User ou lève UserNotFoundException si l’ID est inconnu.
     */
    public function getUserProfile(int $id): User
    {
        $user = $this->em->getRepository(User::class)->find($id);

        if (!$user) {
            throw new UserNotFoundException("Utilisateur $id introuvable");
        }

        return $user;
    }

    /* -----------------------------------------------------------------
       Mise à jour d’un profil
       ----------------------------------------------------------------- */

    /**
     * Met à jour les champs autorisés d’un utilisateur puis effectue la validation.
     *
     * @param User                  $user  Utilisateur à modifier (déjà récupéré)
     * @param array<string,mixed>   $data  Données issues d’un formulaire ou DTO
     *
     * @throws ValidationException  Si une règle métier ou un unique e-mail échoue
     */
    public function updateProfile(User $user, array $data): void
    {
        /* 1. Copie des champs simples facultatifs ---------------------- */
        $simpleFields = ['firstName', 'lastName', 'phone'];

        foreach ($simpleFields as $field) {
            if (\array_key_exists($field, $data)) {
                $setter = 'set' . \ucfirst($field);
                $user->$setter($data[$field]);
            }
        }

        /* 2. Gestion de l’email (unicité et modification) -------------- */
        if (isset($data['email']) && $data['email'] !== $user->getEmail()) {
            $repo = $this->em->getRepository(User::class);

            if ($repo->findOneBy(['email' => $data['email']])) {
                // Aucun ConstraintViolationList n’est fourni par le composant Validator ici ;
                // on en injecte un vide par simplicité (adapter si besoin d’un vrai détail).
                throw new ValidationException(
                    new ConstraintViolationList(),
                    'Adresse e-mail déjà utilisée'
                );
            }

            $user->setEmail($data['email']);
        }

        /* 3. Validation Symfony ---------------------------------------- */
        $violations = $this->validator->validate($user);

        if (\count($violations) > 0) {
            throw new ValidationException($violations);
        }

        /* 4. Persistance ----------------------------------------------- */
        $this->em->flush();
    }
}
