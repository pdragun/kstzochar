<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Location;
use App\Form\LocationMergeType;
use App\Form\LocationType;
use App\Repository\LocationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/** Admin pages for the start locations of invitations: list, edit, merge, delete */
#[IsGranted('ROLE_ADMIN')]
class LocationController extends AbstractController
{
    public function __construct(
        private readonly LocationRepository $locationRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly CacheItemPoolInterface $contentCache,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('/miesta-stretnutia', name: 'location_list', methods: ['GET'])]
    public function list(): Response
    {
        return $this->render('location/list.html.twig', [
            'locations' => $this->locationRepository->findAllWithInvitationCount(),
        ]);
    }

    #[Route('/miesta-stretnutia/{id}/edit', name: 'location_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): RedirectResponse|Response
    {
        $location = $this->findLocation($id);

        $form = $this->createForm(LocationType::class, $location, ['standalone' => true])
            ->add('save', SubmitType::class, ['label' => 'form.locationType.save', 'attr' => ['class' => 'btn-primary']]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->contentCache->clear();
            $this->addFlash('success', $this->translator->trans('templates.location.flash.saved', ['%name%' => $location->getName()]));

            return $this->redirectToRoute('location_list');
        }

        return $this->render('location/edit.html.twig', [
            'form' => $form->createView(),
            'location' => $location,
        ]);
    }

    /** Move the invitations of this location to another one, then delete it */
    #[Route('/miesta-stretnutia/{id}/merge', name: 'location_merge', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function merge(int $id, Request $request): RedirectResponse|Response
    {
        $source = $this->findLocation($id);

        $form = $this->createForm(LocationMergeType::class, null, ['source' => $source]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Location $target */
            $target = $form->get('target')->getData();
            $invitations = $source->getEventInvitations()->toArray();
            foreach ($invitations as $invitation) {
                $invitation->setLocation($target);
            }
            $this->entityManager->remove($source);
            $this->entityManager->flush(); // one transaction; Doctrine runs the updates before the delete
            $this->contentCache->clear();

            $this->addFlash('success', $this->translator->trans('templates.location.flash.merged', [
                '%source%' => $source->getName(),
                '%target%' => $target->getName(),
                '%count%' => count($invitations),
            ]));

            return $this->redirectToRoute('location_edit', ['id' => $target->getId()]);
        }

        return $this->render('location/merge.html.twig', [
            'form' => $form->createView(),
            'location' => $source,
        ]);
    }

    /** Only an unused location can be deleted; the foreign key would silently remove it from its invitations */
    #[Route('/miesta-stretnutia/{id}/delete', name: 'location_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, Request $request): RedirectResponse
    {
        $location = $this->findLocation($id);

        if (!$this->isCsrfTokenValid('delete-location-' . $location->getId(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        if (!$location->getEventInvitations()->isEmpty()) {
            $this->addFlash('danger', $this->translator->trans('templates.location.flash.inUse', ['%name%' => $location->getName()]));

            return $this->redirectToRoute('location_edit', ['id' => $location->getId()]);
        }

        $this->entityManager->remove($location);
        $this->entityManager->flush();
        $this->contentCache->clear();
        $this->addFlash('success', $this->translator->trans('templates.location.flash.deleted', ['%name%' => $location->getName()]));

        return $this->redirectToRoute('location_list');
    }

    private function findLocation(int $id): Location
    {
        return $this->locationRepository->find($id) ?? throw $this->createNotFoundException();
    }
}
