<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Blog;
use App\Form\BlogType;
use App\Repository\BlogRepository;
use App\Repository\BlogSectionRepository;
use App\Utils\SecondLevelCachePDO;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class BlogController extends AbstractController
{

    /** Let user choose section */
    #[Route('/blog', name: 'blog', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('blog/index.html.twig', [
            'controller_name' => 'BlogController',
        ]);
    }

    /** @throws NonUniqueResultException */
    #[Route(
        '/blog/{blogSectionSlug}/{year}/{slug}',
        name: 'blog_show_by_BlogSectionSlug_Year_Slug',
        requirements: ['year' => '\d+'],
        methods: ['GET']
    )]
    public function showBlogByBlogSectionSlugYearSlug(
        string $blogSectionSlug,
        int $year,
        string $slug,
        BlogRepository $blogRepository,
        BlogSectionRepository $blogSectionRepository
    ): Response {
        $blogSection = $blogSectionRepository->findBySlug($blogSectionSlug);
        if ($blogSection === null) {
            throw $this->createNotFoundException();
        }

        $blogSectionId = $blogSection->getId();
        $blog = $blogRepository->findBySectionYearSlug($blogSectionId, $year, $slug);
        if ($blog === null) {
            throw $this->createNotFoundException();
        }

  		return $this->render('blog/showBlogByBlogSectionSlugYearSlug.html.twig', [
            'blog' => $blog,
            'blogSection' => $blogSection,
            'year' => $year,
        ]);
    }

    /** Display all blogs from blog section */
    #[Route('/blog/{blogSectionSlug}', name: 'blog_list_by_BlogSectionSlug', methods: ['GET'])]
    public function showBlogsByBlogSectionSlug(
        string $blogSectionSlug,
        BlogRepository $blogRepository,
        BlogSectionRepository $blogSectionRepository
    ): Response {
        $blogSection = $blogSectionRepository->findBySlug($blogSectionSlug);
        if ($blogSection === null) { // 404
            throw $this->createNotFoundException();
        }

        $blogSectionId = $blogSection->getId();
        if ($blogSection->getId() === 2) { //Multiday events
            $blogs = $blogRepository->getPreparedByYearStartDate($blogSectionId);
        } else {
            $blogs = $blogRepository->getPreparedByYear($blogSectionId);
        }

        if ($blogs === []) {
            throw $this->createNotFoundException();
        }
        
        return $this->render('blog/showBlogByBlogSection.html.twig', [
            'blogSectionSlug' => $blogSectionSlug,
            'blogSection' => $blogSection,
            'blogs' => $blogs,
            'blogSectionId' => $blogSectionId,
        ]);
    }

    /**
     * Create blog
     * @throws NonUniqueResultException
     */
    #[Route('/blog/{blogSectionSlug}/pridat-novy/add', name: 'blog_create', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function createBlog(
        string $blogSectionSlug,
        Request $request,
        BlogSectionRepository $blogSectionRepository,
        ManagerRegistry $doctrine
    ): Response {
        $blogSection = $blogSectionRepository->findBySlug($blogSectionSlug);
        if ($blogSection === null) {
            throw $this->createNotFoundException();
        }

        $blog = new Blog();
        $originalSportTypes = new ArrayCollection();
        if ($blogSection->getId() === 2) { //Only for multiday events
            foreach ($blog->getSportType() as $sportType) {
                $originalSportTypes->add($sportType);
            }
        }

        /** @var $form BlogType */
        $form = $this->createForm(BlogType::class, $blog);
        if ($blogSection->getId() !== 2) {//Only for multiday events
            $form->remove('sportType');
            $form->remove('startDate');
        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var $blog Blog */
            $blog = $form->getData();
            $now = new DateTimeImmutable();
            $slugger = new AsciiSlugger();

            $slug = $slugger->slug($blog->getTitle());
            $blog->setSlug($slug);
            $blog->setPublishedAt($now);
            $blog->setCreatedAt($now);
            $blog->setModifiedAt($now);
            $blog->setPublish(true);
            $blog->setCreatedBy($this->getUser());
            $blog->setSection($blogSection);
            
            $entityManager = $doctrine->getManager();

            if ($blogSection->getId() === 2) {//Only for multiday events
            // remove or update SportTypes for Blog
                foreach ($originalSportTypes as $sportType) {
                    if ($blog->getSportType()->contains($sportType) === false) {
                        $sportType->removeBlog($blog);
                        $entityManager->persist($sportType);
                    }
                }
            }

            $entityManager->persist($blog);
            $entityManager->flush();

            $cache = SecondLevelCachePDO::getInstance();
            $cache->clearAllCache();
    
            $this->addFlash(
                'success',
                sprintf('Nový článok: „%s“ bol vytvorený a uložený!', $blog->getTitle())
            );

            return $this->redirectToRoute('blog_show_by_BlogSectionSlug_Year_Slug', [
                'blogSectionSlug' => $blogSectionSlug,
                'year' => $now->format('Y'),
                'slug' => $blog->getSlug()
            ]);
        }

  		return $this->render('blog/create.html.twig', [
            'form' => $form->createView(),
            'blogSectionSlug' => $blogSectionSlug,
            'blogSection' => $blogSection,
            'title' => 'Vytvoriť nový článok',
            'actionName' => 'Pridať'
        ]);
    }

    /** @throws NonUniqueResultException|InvalidArgumentException */
    #[Route(
        '/blog/{blogSectionSlug}/{year}/{slug}/edit',
        name: 'blog_edit',
        requirements: ['year' => '\d+'],
        methods: ['GET', 'POST']
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function editInvitation(
        string $blogSectionSlug,
        int $year,
        string $slug,
        Request $request,
        BlogRepository $blogRepository,
        BlogSectionRepository $blogSectionRepository,
        ManagerRegistry $doctrine
    ): RedirectResponse|Response {

        $blogSection = $blogSectionRepository->findBySlug($blogSectionSlug);
        if ($blogSection === null) { // 404
            throw $this->createNotFoundException();
        }

        $blog = $blogRepository->findBySectionYearSlug($blogSection->getId(), $year, $slug);
        if ($blog === null) { // 404
            throw $this->createNotFoundException();
        }

        if ($blogSection->getId() === 2) { //Only for multiday events
            $originalSportTypes = new ArrayCollection();
            foreach ($blog->getSportType() as $sportType) {
                $originalSportTypes->add($sportType);
            }
        }

        /** @var $form BlogType */
        $form = $this->createForm(BlogType::class, $blog);
        if ($blogSection->getId() !== 2) {//Only for multiday events
            $form->remove('sportType');
            $form->remove('startDate');
        }
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            /** @var $blog Blog */
            $blog = $form->getData();
            $slugger = new AsciiSlugger();
            $slug = $slugger->slug($blog->getTitle());
            $blog->setSlug($slug);
            $blog->setModifiedAt(new DateTimeImmutable());

            $entityManager = $doctrine->getManager();
            if ($blogSection->getId() === 2) {//Only for multiday events
            // remove or update SportTypes for Blog
                foreach ($originalSportTypes as $sportType) {
                    if ($blog->getSportType()->contains($sportType) === false) {
                        $sportType->removeBlog($blog);
                        $entityManager->persist($sportType);
                    }
                }
            }

            $entityManager->persist($blog);
            $entityManager->flush();

            $cache = SecondLevelCachePDO::getInstance();
            $cache->clearAllCache();
    
            $this->addFlash(
                'success',
                sprintf('Zmeny v článku: „%s“ boli uložené!', $blog->getTitle())
            );
            return $this->redirectToRoute('blog_show_by_BlogSectionSlug_Year_Slug', [
                'blogSectionSlug' => $blogSectionSlug,
                'year' => $year,
                'slug' => $blog->getSlug()
            ]);
        }

  		return $this->render('blog/create.html.twig', [
            'form' => $form->createView(),
            'blogSection' => $blogSection,
            'title' => $blog->getTitle(),
            'year' => $year,
            'actionName' => 'Upraviť'
        ]);
    }

    /**
     * Delete blog
     * @return RedirectResponse Redirect to list of blogs
     * @throws InvalidArgumentException|NonUniqueResultException
     */
    #[Route(
        '/blog/{blogSectionSlug}/{year}/{slug}/delete/yes',
        name: 'blog_delete_yes',
        requirements: ['year' => '\d+'],
        methods: ['GET']
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteBlog(
        string $blogSectionSlug,
        int $year,
        string $slug,
        BlogRepository $blogRepository,
        BlogSectionRepository $blogSectionRepository,
        ManagerRegistry $doctrine
    ): RedirectResponse {

        $blogSection = $blogSectionRepository->findBySlug($blogSectionSlug);
        if ($blogSection === null) {
            throw $this->createNotFoundException();
        }
        
        $blog = $blogRepository->findBySectionYearSlug($blogSection->getId(), $year, $slug);
        if ($blog === null) {
            throw $this->createNotFoundException();
        }

        $blog->removeEvent();
        $blogTitle = $blog->getTitle();

        $entityManager = $doctrine->getManager();
        $entityManager->remove($blog);
        $entityManager->flush();

        $cache = SecondLevelCachePDO::getInstance();
        $cache->clearAllCache();

        $this->addFlash(
            'success',
            sprintf('Článok: „%s“ bol zmazaný!', $blogTitle)
        );

        return $this->redirectToRoute('blog_list_by_BlogSectionSlug', [
            'blogSectionSlug' => $blogSection->getSlug()
        ]);
    }

    /**
     * Confirmation to delete blog
     * @return Response Show blog and ask for confirmation
     * @throws NonUniqueResultException
     */
    #[Route('/blog/{blogSectionSlug}/{year}/{slug}/delete',
        name: 'blog_delete',
        requirements:['year' => '\d+'],
        methods: ['GET']
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function prepareDeleteBlog(
        string $blogSectionSlug,
        int $year,
        string $slug,
        BlogRepository $blogRepository,
        BlogSectionRepository $blogSectionRepository
    ): Response {
        $blogSection = $blogSectionRepository->findBySlug($blogSectionSlug);
        if ($blogSection === null) {
            throw $this->createNotFoundException();
        }

        $blog = $blogRepository->findBySectionYearSlug($blogSection->getId(), $year, $slug);
        if ($blog === null) {
            throw $this->createNotFoundException();
        }

  		return $this->render('blog/delete.html.twig', [
            'blog' => $blog,
            'year' => $year,
            'blogSection' => $blogSection,
        ]);
    }
}
