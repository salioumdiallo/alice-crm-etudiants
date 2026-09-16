<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Customer;
use App\Form\CustomerType;
use Cocur\Slugify\Slugify;
use App\Entity\DynamicContent;
use App\Form\EditCustomerType;
use App\Form\DynamicContentType;
use App\Repository\UserRepository;
use App\Repository\ContactRepository;
use App\Repository\ContractRepository;
use App\Repository\CustomerRepository;
use App\Repository\TariffZoneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Doctrine\Persistence\ManagerRegistry as PersistenceManagerRegistry;

#[Route('/admin')]
class AdminMainController extends AbstractController
{
    private $userRepository;
    private $customerRepository;
    private $contactRepository;
    private $contractRepository;
    private $tariffZoneRepository;
    private $entityManager;

    public function __construct(
        UserRepository $userRepository,
        CustomerRepository $customerRepository,
        ContactRepository $contactRepository,
        ContractRepository $contractRepository,
        TariffZoneRepository $tariffZoneRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->userRepository = $userRepository;
        $this->customerRepository = $customerRepository;
        $this->contactRepository = $contactRepository;
        $this->contractRepository = $contractRepository;
        $this->tariffZoneRepository = $tariffZoneRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/client', name: 'app_customer_list')]
    public function showCustomers(): Response
    {
        $customers = $this->customerRepository->findAll();

        return $this->render('admin_main/customer_list.html.twig', [
            'customers' => $customers
        ]);
    }

    #[Route('/client/{id}/{slug}', name: 'app_customer')]
    public function showCustomer(
        #[MapEntity(mapping: ['id' => 'id', 'slug' => 'slug'])] Customer $id,
        string $slug,
        Request $request
    ): Response
    {
        $customer = $this->customerRepository->findOneById($id);

        if (!$customer) {
            return $this->redirectToRoute('app_customer_list', [], 301);
        }

        if ($customer->getSlug() !== $slug) {
            $this->addFlash(
                'alert',
                'Vous ne pouvez pas faire ça !'
            );

            return $this->redirectToRoute('app_customer_list', [], 301);
        }

        $user = $customer->getUser();

        $contacts = $this->contactRepository->findBy([
            'user' => $user
        ]);

        $contracts = $this->contractRepository->findBy([
            'customer' => $customer
        ]);

        $dynamicContent = new DynamicContent();

        $form = $this->createForm(
            DynamicContentType::class,
            $dynamicContent
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($dynamicContent);
            $this->entityManager->flush();
        }

        return $this->render('admin_main/customer_show.html.twig', [
            'customer' => $customer,
            'user' => $user,
            'contacts' => $contacts,
            'contracts' => $contracts,
            'dynamicContent' => $dynamicContent,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/client/creer-un-client/{id}/{slug}', name: 'app_customer_add')]
    public function createCustomer(
        Request $request,
        EntityManagerInterface $entityManager,
        PersistenceManagerRegistry $doctrine,
        #[MapEntity(mapping: ['id' => 'id'])] User $user
    ): Response
    {
        $user = $this->userRepository->findOneById($user);
        $tariffZone = $this->tariffZoneRepository->findAll();

        if (!$tariffZone) {
            $this->addFlash(
                'notice',
                'Vous n\'avez pas encore définit de zone tarifaire. Merci de renseigner préalablement cet élément. Vous pourrez retourner sur le formulaire de création client par la suite.',
            );

            return $this->redirectToRoute('app_tariff_zone_new');
        }

        $customer = new Customer();

        $form = $this->createForm(CustomerType::class, $customer, [
            'user' => $user
        ]);

        $customer->setUser($user);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $customer = $form->getData();

                if ($customer->getSiret()) {
                    $siret = str_replace(
                        ' ',
                        '',
                        $form->get('siret')->getData()
                    );

                    $customer->setSiret($siret);
                }

                $fullname = $customer->getName();

                $slugify = new Slugify();
                $slugify = $slugify->slugify($fullname);

                $customer->setSlug($slugify);
                $customer->setUser($user);

                $entityManager = $doctrine->getManager();
                $entityManager->persist($customer);
                $entityManager->flush();

                $this->addFlash(
                    'success',
                    'La création du client est bien enregistrée.'
                );

                $customerId = $customer->getId();

                return $this->redirectToRoute('app_customer', [
                    'id' => $customerId,
                    'slug' => $slugify
                ]);
            }
        }

        return $this->render('admin_main/customer_new.html.twig', [
            'form' => $form->createView(),
            'flash' => $this,
            'customer' => $customer,
            'user' => $user
        ]);
    }

    #[Route('/client/{id}/{slug}/modifier-un-client', name: 'app_customer_edit')]
    public function editCustomer(
        Request $request,
        $id,
        $slug
    ): Response
    {
        $customer = $this->customerRepository->findOneById($id);

        if (!$customer) {
            return $this->redirectToRoute(
                'app_customer_list',
                [],
                301
            );
        }

        if ($customer->getSlug() !== $slug) {
            $this->addFlash(
                'alert',
                'Vous ne pouvez pas faire ça !'
            );

            return $this->redirectToRoute(
                'app_customer',
                [
                    'id' => $id,
                    'slug' => $slug
                ],
                301
            );
        }

        $form = $this->createForm(
            EditCustomerType::class,
            $customer
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($customer->getSiret()) {
                $siret = str_replace(
                    ' ',
                    '',
                    $form->get('siret')->getData()
                );

                $customer->setSiret($siret);
            }

            $this->entityManager->flush();

            $this->addFlash(
                'success',
                'La modification du client est bien enregistrée.'
            );

            return $this->redirectToRoute('app_customer', [
                'id' => $id,
                'slug' => $slug
            ]);
        }

        return $this->render('admin_main/customer_edit.html.twig', [
            'form' => $form->createView(),
            'flash' => $this,
            'customer' => $customer
        ]);
    }

    #[Route(
        '/contenu-dynamique/modifier/{id}/{slug}/{name}/',
        name: 'dynamic_content_edit',
        requirements: ['name' => '[a-z0-9_-]{2,50}']
    )]
    public function dynamicContentEdit(
        $name,
        PersistenceManagerRegistry $doctrine,
        Request $request,
        #[MapEntity(mapping: ['id' => 'id', 'slug' => 'slug'])] Customer $customer
    ): Response
    {
        $dynamicContentRepo = $doctrine->getRepository(
            DynamicContent::class
        );

        $currentDynamicContent = $dynamicContentRepo->findOneByName(
            $name
        );

        $em = $doctrine->getManager();

        if (empty($currentDynamicContent)) {
            $currentDynamicContent = new DynamicContent();
            $currentDynamicContent->setName($name);

            $em->persist($currentDynamicContent);
        }

        $form = $this->createForm(
            DynamicContentType::class,
            $currentDynamicContent
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash(
                'success',
                'Le contenu a bien été modifié !'
            );

            return $this->redirectToRoute('app_customer', [
                'id' => $customer->getId(),
                'slug' => $customer->getSlug()
            ]);
        }

        return $this->render('dynamic_content/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
