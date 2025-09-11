<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Service\PseudoGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, UserAuthenticatorInterface $userAuthenticator, FormLoginAuthenticator $formLoginAuthenticator): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);
            $entityManager->flush();

            // Log in the user automatically
            $userAuthenticator->authenticateUser(
                $user,
                $formLoginAuthenticator,
                $request
            );

            // Add success message
            $this->addFlash('success', 'Votre compte a été créé avec succès ! Vous êtes maintenant connecté.');

            return $this->redirectToRoute('app_home');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Une erreur est survenue lors de la création de votre compte. Veuillez vérifier les informations saisies.');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/random-pseudo', name: 'app_random_pseudo', methods: ['GET'])]
    public function randomPseudo(PseudoGenerator $pseudoGenerator): JsonResponse
    {
        return new JsonResponse([
            'pseudo' => $pseudoGenerator->getRandomPseudo(),
        ]);
    }
}
