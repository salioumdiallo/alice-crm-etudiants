<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Contact;
use App\Form\ContactType;
use App\Repository\ContactRepository;
use App\Repository\UserRepository;
use Cocur\Slugify\Slugify;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/contact')]
class ContactController extends AbstractController
{
    public function __construct(
        private ContactRepository $contactRepository,
        private UserRepository $userRepository,
    ) {
    }

    #[Route('/', name: 'app_contacts')]
    public function showContacts(): Response
    {
        $contacts = $this->contactRepository->findAll();

        return $this->render('admin/contact_list.html.twig', [
            'contacts' => $contacts,
        ]);
    }

    #[Route('/{id}/{slug}', name: 'app_contact')]
    public function showContact(int $id): Response
    {
        $contact = $this->contactRepository->findOneById($id);

        if (!$contact) {
            return $this->redirectToRoute('app_contacts');
        }

        $user = $contact->getUser();
        $customer = $user->getCustomer();

        return $this->render('admin/contact_show.html.twig', [
            'contact' => $contact,
            'user' => $user,
            'customer' => $customer,
        ]);
    }

    #[Route('/creer-un-contact/{id}/{slug}', name: 'app_contact_add')]
    public function createContact(Request $request, int $id): Response
    {
        $user = $this->userRepository->findOneById($id);

        if (!$user) {
            $this->addFlash(
                'error',
                'L\'utilisateur n\'existe pas.'
            );

            return $this->redirectToRoute('app_user_list');
        }

        $slug = $user->getSlug();
        $customer = $user->getCustomer();

        $contact = new Contact();
        $contact->setUser($user);

        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fullname = $contact->getFirstname().' '.$contact->getLastname();

            $slugify = new Slugify();
            $contact->setSlug($slugify->slugify($fullname));

            $this->contactRepository->save($contact, true);

            $this->addFlash(
                'success',
                'La création du contact est bien enregistrée.'
            );

            if ($customer) {
                return $this->redirectToRoute('app_customer', [
                    'id' => $customer->getId(),
                    'slug' => $customer->getSlug(),
                ]);
            }

            return $this->redirectToRoute('app_user_show', [
                'id' => $id,
                'slug' => $slug,
            ]);
        }

        return $this->render('admin/contact_new.html.twig', [
            'user' => $user,
            'flash' => $this,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/modifier-un-contact/{id}/{slug}', name: 'app_contact_edit')]
    public function editContact(Request $request, int $id, string $slug): Response
    {
        $contact = $this->contactRepository->findOneById($id);

        if (!$contact) {
            $this->addFlash(
                'error',
                'Le contact n\'existe pas.'
            );

            return $this->redirectToRoute('app_contacts');
        }

        $user = $contact->getUser();
        $customer = $user->getCustomer();

        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fullname = $contact->getFirstname().' '.$contact->getLastname();

            $slugify = new Slugify();
            $contact->setSlug($slugify->slugify($fullname));

            $this->contactRepository->save($contact, true);

            $this->addFlash(
                'success',
                'La modification du contact est bien enregistrée.'
            );

            return $this->redirectToRoute('app_contact', [
                'id' => $contact->getId(),
                'slug' => $contact->getSlug(),
            ]);
        }

        return $this->render('admin/contact_edit.html.twig', [
            'form' => $form->createView(),
            'flash' => $this,
            'user' => $user,
            'customer' => $customer,
        ]);
    }

    #[Route('/{id}/{slug}/supprimer', name: 'app_contact_delete', methods: ['POST'])]
    public function deleteContact(Request $request, int $id): Response
    {
        $contact = $this->contactRepository->findOneById($id);

        if (!$contact) {
            $this->addFlash(
                'error',
                'Le contact n\'existe pas.'
            );

            return $this->redirectToRoute('app_contacts');
        }

        $user = $contact->getUser();
        $userId = $user->getId();
        $slug = $user->getSlug();

        $csrfToken = $request->request->get('_token', '');

        if (!$this->isCsrfTokenValid('delete_contact'.$contact->getId(), $csrfToken)) {
            $this->addFlash(
                'error',
                'Vous ne pouvez pas supprimer cet élément.'
            );

            if ($customer = $user->getCustomer()) {
                return $this->redirectToRoute('app_customer', [
                    'id' => $customer->getId(),
                    'slug' => $customer->getSlug(),
                ]);
            }

            return $this->redirectToRoute('app_user_show', [
                'id' => $userId,
                'slug' => $slug,
            ]);
        }

        $customer = $user->getCustomer();

        $this->contactRepository->remove($contact, true);

        $this->addFlash(
            'success',
            'Le contact a été supprimé.'
        );

        if ($customer) {
            return $this->redirectToRoute('app_customer', [
                'id' => $customer->getId(),
                'slug' => $customer->getSlug(),
            ]);
        }

        return $this->redirectToRoute('app_user_show', [
            'id' => $userId,
            'slug' => $slug,
        ]);
    }
}
