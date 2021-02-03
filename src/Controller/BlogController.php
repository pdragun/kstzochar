<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Blog;
use App\Form\BlogType;
use App\Repository\BlogRepository;
use App\Repository\BlogSectionRepository;
use App\Utils\SecondLevelCachePDO;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;

class BlogController extends AbstractController
{

    /**
     * Let user to choose section
     * 
     * @Route("/blog", name="blog")
     * 
     * @return Symfony\Component\HttpFoundation\Response Show page with options
     */
    public function index(): Response
    {
        return $this->render('blog/index.html.twig', [
            'controller_name' => 'BlogController',
        ]);
    }
    

    /**
     * Show blog according to section slug, year and blog slug
     * 
     * @Route("/blog/{blogSectionSlug}/{year}/{slug}", name="blog_show_by_BlogSectionSlug_Year_Slug", requirements={"year"="\d+"})
     * 
     * @param string $blogSection Section slug
     * @param int $year Year
     * @param string $slug Blog slug
     * @param App\Repository\BlogRepository $blogRepository
     * @param App\Repository\BlogSectionRepository $blogSectionRepository
     * @return Symfony\Component\HttpFoundation\Response Show blog
     */
    public function showBlogByBlogSectionSlugYearSlug(string $blogSectionSlug, int $year, string $slug, BlogRepository $blogRepository, BlogSectionRepository $blogSectionRepository): Response
    {

        /** @var App\Entity\BlogSection $blogSection **/
        $blogSection = $blogSectionRepository->findBySlug($blogSectionSlug);
        if(!$blogSection) { // 404
            throw $this->createNotFoundException();
        }


        $blogSectionId = $blogSection->getId();

        /** @var App\Entity\Blog $blog **/
        $blog = $blogRepository->findBySectionYearSlug($blogSectionId, $year, $slug);
        if(!$blog) { // 404
            throw $this->createNotFoundException();
        }

  		return $this->render('blog/showBlogByBlogSectionSlugYearSlug.html.twig', [
            'blog' => $blog,
            'blogSection' => $blogSection,
            'year' => $year,
        ]);
    }


    /**
     * Display all blogs from blog section
     *
     * @Route("/blog/{blogSectionSlug}", name="blog_list_by_BlogSectionSlug")
     * 
     * @param string $blogSectionSlug Section slug
     * @param App\Repository\BlogRepository $blogRepository
     * @param App\Repository\BlogSectionRepository $blogSectionRepository
     * @return Symfony\Component\HttpFoundation\Response Display all blogs from blog section
     */
    public function showBlogsByBlogSectionSlug(string $blogSectionSlug, BlogRepository $blogRepository, BlogSectionRepository $blogSectionRepository): Response
    {
        /** @var App\Entity\BlogSection $blogSection **/
        $blogSection = $blogSectionRepository->findBySlug($blogSectionSlug);
        if(!$blogSection) { // 404
            throw $this->createNotFoundException();
        }

        $blogSectionId = $blogSection->getId();

       
        if($blogSection->getId() === 2) { //Multiday events
            /** @var App\Entity\Blog[] $blog **/
            $blogs = $blogRepository->getPreparedByYearStartDate($blogSectionId);
        } else {
            /** @var App\Entity\Blog[] $blog **/
            $blogs = $blogRepository->getPreparedByYear($blogSectionId);
        }

        
        if(!$blogs) { // 404
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
     * 
     * @Route("/blog/{blogSectionSlug}/pridat-novy/add", name="blog_create")
     * @IsGranted("ROLE_ADMIN")
     * 
     * @param string $blogSectionSlug Section slug
     * @param Symfony\Component\HttpFoundation\Request
     * @param App\Repository\BlogSectionRepository $blogSectionRepository
     * @param Doctrine\ORM\EntityManagerInterface $entityManager
     * @return Symfony\Component\HttpFoundation\Response Redirect to created blog or display form to create blog
     */
    public function createBlog(string $blogSectionSlug, Request $request, BlogSectionRepository $blogSectionRepository, EntityManagerInterface $entityManager): Response
    {
        $now = new \DateTime();
        
        /** @var App\Entity\BlogSection $blogSection */
        $blogSection = $blogSectionRepository->findBySlug($blogSectionSlug);
        if(!$blogSection) { // 404
            throw $this->createNotFoundException();
        }


        /** @var App\Entity\Blog $blog */
        $blog = new Blog();

        if($blogSection->getId() === 2) { //Only for multiday events
            $originalSportTypes = new ArrayCollection();
            foreach ($blog->getSportType() as $sportType) {
                $originalSportTypes->add($sportType);
            }
        }

        /** @var App\Form\BlogType; $form **/
        $form = $this->createForm(BlogType::class, $blog);
        if($blogSection->getId() !== 2) {//Only for multiday events
            $form->remove('sportType');
            $form->remove('startDate');
        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $blog = $form->getData();

            $slugger = new AsciiSlugger();
            $slug = $slugger->slug($blog->getTitle());
            $blog->setSlug(\strval($slug));
            $blog->setPublishedAt($now);
            $blog->setCreatedAt($now);
            $blog->setModifiedAt($now);
            $blog->setPublish(TRUE);
            $blog->setCreatedBy($this->getUser());
            $blog->setSection($blogSection);
            
            /** @var Doctrine\Persistence\ManagerRegistry $entityManager */
            $entityManager = $this->getDoctrine()->getManager();

            if($blogSection->getId() === 2) {//Only for multiday events
            // remove or update SportTypes for Blog
                foreach ($originalSportTypes as $sportType) {
                    if (FALSE === $blog->getSportType()->contains($sportType)) {
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
                'Nový článok: "' . $blog->getTitle() . '" bol vytvorený a uložený!'
            );

            return $this->redirectToRoute('blog_show_by_BlogSectionSlug_Year_Slug', ['blogSectionSlug' => $blogSectionSlug, 'year' => $now->format('Y'), 'slug' => $blog->getSlug()]);
        }

  		return $this->render('blog/create.html.twig', [
            'form' => $form->createView(),
            'blogSectionSlug' => $blogSectionSlug,
            'blogSection' => $blogSection,
            'title' => 'Vytvoriť nový článok',
            'actionName' => 'Pridať'
        ]);

    }


    /**
     * Edit blog
     * 
     * @Route("/blog/{blogSectionSlug}/{year}/{slug}/edit", name="blog_edit", requirements={"year"="\d+"})
     * @IsGranted("ROLE_ADMIN")
     * 
     * @param string $blogSectionSlug Section slug
     * @param int $year
     * @param string $slug Blog slug
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param App\Repository\BlogSectionRepository $blogSectionRepository
     * @param Doctrine\ORM\EntityManagerInterface $entityManager
     * @return Symfony\Component\HttpFoundation\Response Redirect to saved blog or display form to edit blog
     */
    public function editInvitation(string $blogSectionSlug, int $year, string $slug, Request $request, BlogRepository $blogRepository, BlogSectionRepository $blogSectionRepository, EntityManagerInterface $entityManager): Response
    {

        /** @var \App\Entity\BlogSection $blogSection **/
        $blogSection = $blogSectionRepository->findBySlug($blogSectionSlug);
        if(!$blogSection) { // 404
            throw $this->createNotFoundException();
        }

        /** @var \App\Entity\Blog $blog **/
        $blog = $blogRepository->findBySectionYearSlug($blogSection->getId(), $year, $slug);
        if(!$blog) { // 404
            throw $this->createNotFoundException();
        }

        if($blogSection->getId() === 2) { //Only for multiday events
            $originalSportTypes = new ArrayCollection();
            foreach ($blog->getSportType() as $sportType) {
                $originalSportTypes->add($sportType);
            }
        }

        /** @var App\Form\BlogType; $form **/
        $form = $this->createForm(BlogType::class, $blog);
        if($blogSection->getId() !== 2) {//Only for multiday events
            $form->remove('sportType');
            $form->remove('startDate');
        }
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            /** @var \App\Entity\Blog $blog **/
            $blog = $form->getData();

            $slugger = new AsciiSlugger();
            $slug = $slugger->slug($blog->getTitle());
            $blog->setSlug(\strval($slug));
            $blog->setModifiedAt(new \DateTime('now'));
            
            if($blogSection->getId() === 2) {//Only for multiday events
            // remove or update SportTypes for Blog
                foreach ($originalSportTypes as $sportType) {
                    if (FALSE === $blog->getSportType()->contains($sportType)) {
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
                'Zmeny v článku: „' . $blog->getTitle() . '“ boli uložené!'
            );
            return $this->redirectToRoute('blog_show_by_BlogSectionSlug_Year_Slug', ['blogSectionSlug' => $blogSectionSlug, 'year' => $year, 'slug' => $blog->getSlug()]);
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
     * 
     * @Route("/blog/{blogSectionSlug}/{year}/{slug}/delete/yes", name="blog_delete_yes", requirements={"year"="\d+"})
     * @IsGranted("ROLE_ADMIN")
     * 
     * @param string $blogSectionSlug Section slug
     * @param int $year
     * @param string $slug Blog slug
     * @param App\Repository\BlogRepository $blogRepository
     * @param App\Repository\BlogSectionRepository $blogSectionRepository
     * @param Doctrine\ORM\EntityManagerInterface $entityManager
     * @return Symfony\Component\HttpFoundation\Response Redirect to list of blogs
     */
    public function deleteBlog(string $blogSectionSlug, int $year, string $slug, BlogRepository $blogRepository, BlogSectionRepository $blogSectionRepository, EntityManagerInterface $entityManager): Response
    {

        /** @var App\Entity\BlogSection $blogSection */
        $blogSection = $blogSectionRepository->findBySlug($blogSectionSlug);
        if(!$blogSection) { // 404
            throw $this->createNotFoundException();
        }
        

        /** @var App\Entity\Blog $blog */
        $blog = $blogRepository->findBySectionYearSlug($blogSection->getId(), $year, $slug);
        if(!$blog) { // 404
            throw $this->createNotFoundException();
        }

        
        $blog->removeEvent();
        $blogTitle = $blog->getTitle();


        $entityManager->remove($blog);
        $entityManager->flush();


        $cache = SecondLevelCachePDO::getInstance();
        $cache->clearAllCache();


        $this->addFlash(
            'success',
            'Článok: „' . $blogTitle . '“ bol zmazaný!'
        );  
        return $this->redirectToRoute('blog_list_by_BlogSectionSlug', ['blogSectionSlug' => $blogSection->getSlug()]);
    }

    
    /**
     * Confirmation to delete blog
     * 
     * @Route("/blog/{blogSectionSlug}/{year}/{slug}/delete", name="blog_delete", requirements={"year"="\d+"})
     * @IsGranted("ROLE_ADMIN")
     * 
     * @param string $blogSectionSlug Section slug
     * @param int $year
     * @param string $slug Blog slug
     * @param App\Repository\BlogRepository $blogRepository
     * @param App\Repository\BlogSectionRepository $blogSectionRepository
     * @return Symfony\Component\HttpFoundation\Response Show blog and ask for confirmation
     */
    public function prepareDeleteBlog(string $blogSectionSlug, int $year, string $slug, BlogRepository $blogRepository, BlogSectionRepository $blogSectionRepository): Response
    {

        /** @var \App\Entity\BlogSection $blogSection **/
        $blogSection = $blogSectionRepository->findBySlug($blogSectionSlug);
        if(!$blogSection) { // 404
            throw $this->createNotFoundException();
        }


        /** @var \App\Entity\Blog $blog **/
        $blog = $blogRepository->findBySectionYearSlug($blogSection->getId(), $year, $slug);
        if(!$blog) { // 404
            throw $this->createNotFoundException();
        }


  		return $this->render('blog/delete.html.twig', [
            'blog' => $blog,
            'year' => $year,
            'blogSection' => $blogSection,
        ]);
    }
}
