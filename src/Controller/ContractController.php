<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Contract;
use App\Entity\SerpInfo;
use App\Entity\SerpResult;
use App\Form\ContractType;
use App\Form\SerpInfoType;
use App\Form\SerpResultType;
use App\Repository\ContractRepository;
use App\Repository\CustomerRepository;
use App\Service\GoogleSearchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/contrat')]
class ContractController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ContractRepository $contractRepository,
        private CustomerRepository $customerRepository,
        private GoogleSearchService $googleSearchService,
    ) {
    }

    #[Route('/', name: 'app_contract_list')]
    public function showContracts(): Response
    {
        $contracts = $this->contractRepository->findAll();

        return $this->render('admin_main/contract_list.html.twig', [
            'contracts' => $contracts,
        ]);
    }

    #[Route('/{id}', name: 'app_contract_show')]
    public function showContract(int $id, Request $request): Response
    {
        $contract = $this->contractRepository->findOneById($id);

        if (!$contract) {
            $this->addFlash(
                'error',
                'Le contrat n\'existe pas.'
            );

            return $this->redirectToRoute('app_contract_list');
        }

        $serpInfos = $contract->getSerpInfos();

        $serpInfoForm = $this->createForm(SerpInfoType::class);
        $serpInfoForm->handleRequest($request);

        if ($serpInfoForm->isSubmitted() && $serpInfoForm->isValid()) {
            $newSerpInfo = new SerpInfo();
            $newKeyword = $serpInfoForm->get('keyword')->getData();

            $newSerpInfo->setKeyword($newKeyword);
            $newSerpInfo->setContract($contract);

            $this->entityManager->persist($newSerpInfo);
            $this->entityManager->flush();

            $this->addFlash(
                'success',
                'Mot clé enregistré avec succès.'
            );

            return $this->redirectToRoute('app_contract_show', [
                'id' => $id,
            ]);
        }

        $serpResultForm = $this->createForm(SerpResultType::class);
        $serpResultForm->handleRequest($request);

        if ($serpResultForm->isSubmitted() && $serpResultForm->isValid()) {
            $newSerpResults = [];

            foreach ($serpInfos as $serpInfo) {
                $keyword = $serpInfo->getKeyword();

                $newRank = $this->googleSearchService->findWebsiteRank(
                    $keyword,
                    $contract->getWebsiteLink()
                );

                if (null === $newRank) {
                    continue;
                }

                $newSerpResult = new SerpResult();
                $newSerpResult->setGoogleRank($newRank);
                $newSerpResult->setSerpInfo($serpInfo);
                $newSerpResult->setDate(new \DateTime());

                $newSerpResults[] = $newSerpResult;
            }

            if ([] !== $newSerpResults) {
                foreach ($newSerpResults as $newSerpResult) {
                    $this->entityManager->persist($newSerpResult);
                }

                $this->entityManager->flush();

                $this->addFlash(
                    'success',
                    'Rangs enregistrés avec succès.'
                );
            } else {
                $this->addFlash(
                    'error',
                    'Le site n\'a été trouvé dans les résultats de recherche Google pour aucun des mots-clés.'
                );
            }

            return $this->redirectToRoute('app_contract_show', [
                'id' => $id,
            ]);
        }

        return $this->render('admin_main/contract_show.html.twig', [
            'serpInfoForm' => $serpInfoForm->createView(),
            'contract' => $contract,
            'serpInfos' => $serpInfos,
            'serpResultForm' => $serpResultForm->createView(),
        ]);
    }

    #[Route('/{id}/{slug}/creer-un-contrat', name: 'app_contract_add')]
    public function createContract(Request $request, int $id): Response
    {
        $customer = $this->customerRepository->findOneById($id);

        if (!$customer) {
            $this->addFlash(
                'error',
                'Le client n\'existe pas.'
            );

            return $this->redirectToRoute('app_customer_list');
        }

        $contract = new Contract();
        $contract->setCustomer($customer);

        $form = $this->createForm(ContractType::class, $contract);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->contractRepository->save($contract, true);

            $this->addFlash(
                'success',
                'La création du contrat est bien enregistrée.'
            );

            return $this->redirectToRoute('app_customer', [
                'id' => $customer->getId(),
                'slug' => $customer->getSlug(),
            ]);
        }

        return $this->render('admin_main/contract_new.html.twig', [
            'customer' => $customer,
            'user' => $customer->getUser(),
            'flash' => $this,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/modifier-un-contrat', name: 'app_contract_edit')]
    public function editContract(Request $request, int $id): Response
    {
        $contract = $this->contractRepository->findOneById($id);

        if (!$contract) {
            $this->addFlash(
                'error',
                'Le contrat n\'existe pas.'
            );

            return $this->redirectToRoute('app_customer_list');
        }

        $customer = $contract->getCustomer();
        $user = $customer->getUser();

        $form = $this->createForm(ContractType::class, $contract);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->contractRepository->save($contract, true);

            $this->addFlash(
                'success',
                'La modification du contrat est bien enregistrée.'
            );

            return $this->redirectToRoute('app_contract_show', [
                'id' => $contract->getId(),
            ]);
        }

        return $this->render('admin_main/contract_edit.html.twig', [
            'contract' => $contract,
            'form' => $form->createView(),
            'user' => $user,
            'customer' => $customer,
            'id' => $user->getId(),
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_contract_remove', methods: ['POST'])]
    public function removeContract(Contract $contract, Request $request): Response
    {
        $customer = $contract->getCustomer();

        $customerId = $customer->getId();
        $customerSlug = $customer->getSlug();

        $csrfToken = $request->request->get('_token', '');

        if (!$this->isCsrfTokenValid('delete_contract'.$contract->getId(), $csrfToken)) {
            $this->addFlash(
                'error',
                'Vous ne pouvez pas supprimer cet élément.'
            );
        } else {
            $this->contractRepository->remove($contract, true);

            $this->addFlash(
                'success',
                'Le contrat a bien été supprimé.'
            );
        }

        return $this->redirectToRoute('app_customer', [
            'id' => $customerId,
            'slug' => $customerSlug,
        ]);
    }

    #[Route('/{id}/supprimer-serp-info/{serpInfoId}', name: 'app_serp_info_remove', methods: ['POST'])]
    public function removeSerpInfo(
        #[MapEntity(id: 'serpInfoId')] SerpInfo $serpInfo,
        Request $request,
    ): Response {
        $contract = $serpInfo->getContract();
        $contractId = $contract->getId();

        $csrfToken = $request->request->get('_token', '');

        if (!$this->isCsrfTokenValid('delete_serp_info'.$serpInfo->getId(), $csrfToken)) {
            $this->addFlash(
                'error',
                'Vous ne pouvez pas supprimer cet élément.'
            );
        } else {
            $this->entityManager->remove($serpInfo);
            $this->entityManager->flush();

            $this->addFlash(
                'success',
                'Le mot clé a bien été supprimé.'
            );
        }

        return $this->redirectToRoute('app_contract_show', [
            'id' => $contractId,
        ]);
    }
}
