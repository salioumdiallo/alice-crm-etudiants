<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\DynamicContent;
use App\Entity\User;
use App\Form\CustomerType;
use App\Form\DynamicContentType;
use App\Repository\ContactRepository;
use App\Repository\ContractRepository;
use App\Repository\CustomerRepository;
use App\Repository\TariffZoneRepository;
use Cocur\Slugify\Slugify;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class CustomerController extends AbstractController
{
    public function __construct(
        private CustomerRepository $customerRepository,
        private ContactRepository $contactRepository,
        private ContractRepository $contractRepository,
        private TariffZoneRepository $tariffZoneRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/client', name: 'app_customer_list')]
    public function showCustomers(): Response
    {
        $customers = $this->customerRepository->findAll();

        return $this->render('admin_main/customer_list.html.twig', [
            'customers' => $customers,
        ]);
    }

    #[Route('/client/{id}/{slug}', name: 'app_customer')]
    public function showCustomer(
        #[MapEntity(mapping: ['id' => 'id', 'slug' => 'slug'])] Customer $customer,
        string $slug,
        Request $request,
    ): Response {
        if ($customer->getSlug() !== $slug) {
            $this->addFlash(
                'alert',
                'Vous ne pouvez pas faire ça !'
            );

            return $this->redirectToRoute('app_customer_list', [], 301);
        }

        $user = $customer->getUser();

        $contacts = $this->contactRepository->findBy([
            'user' => $user,
        ]);

        $contracts = $this->contractRepository->findBy([
            'customer' => $customer,
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
        #[MapEntity(mapping: ['id' => 'id'])] User $user,
    ): Response {
        $tariffZones = $this->tariffZoneRepository->findAll();

        if ([] === $tariffZones) {
            $this->addFlash(
                'notice',
                'Vous n\'avez pas encore défini de zone tarifaire. Merci de renseigner préalablement cet élément. Vous pourrez retourner sur le formulaire de création client par la suite.'
            );

            return $this->redirectToRoute('app_tariff_zone_new');
        }

        $customer = new Customer();
        $customer->setUser($user);

        $form = $this->createForm(CustomerType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($customer->getSiret()) {
                $siret = str_replace(
                    ' ',
                    '',
                    (string) $form->get('siret')->getData()
                );

                $customer->setSiret($siret);
            }

            $slugify = new Slugify();
            $slug = $slugify->slugify($customer->getName());

            $customer->setSlug($slug);

            $this->entityManager->persist($customer);
            $this->entityManager->flush();

            $this->addFlash(
                'success',
                'La création du client est bien enregistrée.'
            );

            return $this->redirectToRoute('app_customer', [
                'id' => $customer->getId(),
                'slug' => $slug,
            ]);
        }

        return $this->render('admin_main/customer_new.html.twig', [
            'form' => $form->createView(),
            'flash' => $this,
            'customer' => $customer,
            'user' => $user,
        ]);
    }

    #[Route('/client/{id}/{slug}/modifier-un-client', name: 'app_customer_edit')]
    public function editCustomer(
        Request $request,
        int $id,
        string $slug,
    ): Response {
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

            return $this->redirectToRoute('app_customer', [
                'id' => $customer->getId(),
                'slug' => $customer->getSlug(),
            ]);
        }

        $form = $this->createForm(
            CustomerType::class,
            $customer
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($customer->getSiret()) {
                $siret = str_replace(
                    ' ',
                    '',
                    (string) $form->get('siret')->getData()
                );

                $customer->setSiret($siret);
            }

            $slugify = new Slugify();
            $customer->setSlug(
                $slugify->slugify($customer->getName())
            );

            $this->entityManager->flush();

            $this->addFlash(
                'success',
                'La modification du client est bien enregistrée.'
            );

            return $this->redirectToRoute('app_customer', [
                'id' => $customer->getId(),
                'slug' => $customer->getSlug(),
            ]);
        }

        return $this->render('admin_main/customer_edit.html.twig', [
            'form' => $form->createView(),
            'flash' => $this,
            'customer' => $customer,
        ]);
    }
}
