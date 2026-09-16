<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Contact;
use App\Form\ContactType;
use App\Repository\ContactRepository;
use App\Repository\DocumentRepository;
use Cocur\Slugify\Slugify;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/compte')]
class HomeController extends AbstractController
{
    public function __construct(
        private DocumentRepository $documentRepository,
        private ContactRepository $contactRepository,
    ) {
    }

    #[Route('', name: 'app_home')]
    public function index(PaginatorInterface $paginator, Request $request): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if (!$user->getIsVerified()) {
            $this->addFlash(
                'alert',
                'Votre compte n\'a pas été vérifié. Veuillez vérifier votre boîte mail, ainsi que les spams.'
            );

            return $this->redirectToRoute('app_logout');
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            $query = $this->documentRepository
                ->createQueryBuilder('d')
                ->orderBy('d.date', 'DESC');
        } else {
            $query = $this->documentRepository
                ->createQueryBuilder('d')
                ->join('d.user', 'u')
                ->where('u.id = :userId')
                ->setParameter('userId', $user->getId())
                ->orderBy('d.date', 'DESC');
        }

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        $contacts = $user->getContacts();

        return $this->render('home/index.html.twig', [
            'pagination' => $pagination,
            'contacts' => $contacts,
            'flash' => $this,
            'user' => $user,
        ]);
    }

    #[Route('/contacts', name: 'app_contacts_user')]
    public function showUserContacts(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('home/contact_user_list.html.twig', [
            'contacts' => $user->getContacts(),
            'user' => $user,
        ]);
    }

    #[Route('/creer-un-contact', name: 'app_contact_user_add')]
    public function addUserContact(Request $request): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

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

            return $this->redirectToRoute('app_contacts_user');
        }

        return $this->render('home/contact_user_add.html.twig', [
            'user' => $user,
            'flash' => $this,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/contacts/{id}/{slug}/modifier-un-contact', name: 'app_contacts_user_edit')]
    public function editUserContact(Request $request, int $id): Response
    {
        $contact = $this->contactRepository->findOneById($id);

        if (!$contact) {
            $this->addFlash(
                'error',
                'Le contact n\'existe pas.'
            );

            return $this->redirectToRoute('app_home');
        }

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

            return $this->redirectToRoute('app_contacts_user');
        }

        return $this->render('home/contact_user_edit.html.twig', [
            'form' => $form->createView(),
            'flash' => $this,
        ]);
    }
}
