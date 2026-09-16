<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\DynamicContent;
use App\Form\DynamicContentType;
use Doctrine\Persistence\ManagerRegistry as PersistenceManagerRegistry;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminMainController extends AbstractController
{
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
    ): Response {
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
                'slug' => $customer->getSlug(),
            ]);
        }

        return $this->render('dynamic_content/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
