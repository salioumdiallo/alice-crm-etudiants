<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\EditUserType;
use App\Form\NewUserType;
use App\Repository\CustomerRepository;
use App\Repository\UserRepository;
use Cocur\Slugify\Slugify;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class UserController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private CustomerRepository $customerRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/utilisateur', name: 'app_user_list')]
    public function showUserList(): Response
    {
        $users = $this->userRepository->findAll();
        $customers = $this->customerRepository->findAll();

        return $this->render('admin_main/user_list.html.twig', [
            'users' => $users,
            'customer' => $customers,
        ]);
    }

    #[Route('/utilisateur/{id}/{slug}', name: 'app_user_show')]
    public function showUser(
        #[MapEntity(mapping: ['id' => 'id', 'slug' => 'slug'])] User $user,
        string $slug
    ): Response {
        if ($user->getSlug() !== $slug) {
            $this->addFlash(
                'alert',
                'Vous ne pouvez pas faire ça !'
            );

            return $this->redirectToRoute('app_user_list');
        }

        $contacts = $user->getContacts();
        $customer = $user->getCustomer();

        return $this->render('admin_main/user_show.html.twig', [
            'user' => $user,
            'contacts' => $contacts,
            'customer' => $customer,
            'flash' => $this,
        ]);
    }

    #[Route('/nouvel-utilisateur', name: 'app_user_add')]
    public function addUser(
        Request $request,
        ManagerRegistry $doctrine,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = new User();

        $form = $this->createForm(NewUserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();

            $searchEmail = $this->userRepository->findOneByEmail($user->getEmail());

            if (!$searchEmail) {
                $password = $passwordHasher->hashPassword($user, $user->getPassword());
                $user->setPassword($password);

                $fullname = $user->getFirstname().' '.$user->getLastname();
                $slugify = new Slugify();
                $slug = $slugify->slugify($fullname);
                $user->setSlug($slug);

                $entityManager = $doctrine->getManager();
                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash(
                    'success',
                    'Le nouvel utilisateur est enregistré.'
                );

                return $this->redirectToRoute('app_user_show', [
                    'id' => $user->getId(),
                    'slug' => $slug,
                ]);
            }

            $this->addFlash(
                'alert',
                'L\'email que vous avez renseigné existe déjà !!'
            );

            return $this->redirectToRoute('app_user_list');
        }

        return $this->render('admin_main/user_new.html.twig', [
            'form' => $form->createView(),
            'flash' => $this,
        ]);
    }

    #[Route('/utilisateur/{id}/{slug}/modifier', name: 'app_user_edit')]
    public function editUser(
        Request $request,
        int $id,
        string $slug,
        ManagerRegistry $doctrine,
        #[MapEntity(mapping: ['id' => 'id', 'slug' => 'slug'])] User $user
    ): Response {
        $form = $this->createForm(EditUserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $user = $form->getData();

                $entityManager = $doctrine->getManager();
                $entityManager->flush();

                $this->addFlash(
                    'success',
                    'La modification du contact est bien enregistrée.'
                );

                return $this->redirectToRoute('app_user_show', [
                    'id' => $id,
                    'slug' => $slug,
                ]);
            }

            $this->addFlash(
                'alert',
                'Erreur sur le formulaire.'
            );

            return $this->redirectToRoute('app_user_list');
        }

        return $this->render('admin_main/user_edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
}
