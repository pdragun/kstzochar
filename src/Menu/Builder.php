<?php

declare(strict_types=1);

namespace App\Menu;

use App\Entity\Blog;
use App\Entity\BlogSection;
use App\Entity\Event;
use App\Entity\EventChronicle;
use App\Entity\EventInvitation;
use App\Utils\SecondLevelCachePDO;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\ItemInterface as CacheItemInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Contracts\Translation\TranslatorInterface;

final class Builder implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function __construct(private readonly FactoryInterface $factory, private readonly ManagerRegistry $doctrine, private readonly TranslatorInterface $translator)  {}

    /** @throws InvalidArgumentException */
    public function createMainMenu(array $options): ItemInterface
    {
        $cachedData = $this->getData();

        $menu = $this->factory->createItem(
            $this->translator->trans('menu.homepage1'),
            ['route' => 'home_page'],
        );
        $menu->setChildrenAttribute('class', 'navbar-nav mr-auto');

        $menu->addChild(
            $this->translator->trans('menu.homepage2'),
            ['route' => 'home_page'],
        );

        //Invitation, all articles in all years
        $menu->addChild(
            $this->translator->trans('menu.invitations'),
            [
                'route' => 'invitation_show',
                'attributes' => ['dropdown' => 'true'],
            ]
        );

        foreach ($cachedData[$this->translator->trans('menu.invitations')] as $year => $eventList) {
            $menu[$this->translator->trans('menu.invitations')]->addChild(
                $year,
                [
                    'route' => 'invitation_list_by_Year',
                    'routeParameters' => ['year' => $year],
                ],
            );
            $menu[$this->translator->trans('menu.invitations')][$year]->setDisplayChildren(false);

            foreach ($eventList as $event) {
                $menu[$this->translator->trans('menu.invitations')][$year]->addChild(
                    $event['title'],
                    [
                        'route' => 'invitation_show_by_Year_by_Slug',
                        'routeParameters' => ['year' => $year, 'slug' => $event['slug']],
                    ]
                );
            }
        }

        //Chronicle, all articles in all years
        $menu->addChild(
            $this->translator->trans('menu.chronicle'),
            [
                'route' => 'chronicle_show',
                'attributes' => ['dropdown' => 'true'],
            ]
        );

        foreach ($cachedData[$this->translator->trans('menu.chronicle')] as $year => $eventList) {
            $menu[$this->translator->trans('menu.chronicle')]->addChild(
                $year,
                [
                    'route' => 'chronicle_list_by_Year',
                    'routeParameters' => ['year' => $year],
                ]
            );
            $menu[$this->translator->trans('menu.chronicle')][$year]->setDisplayChildren(false);

            foreach ($eventList as $event) {
                $menu[$this->translator->trans('menu.chronicle')][$year]->addChild(
                    $event['title'],
                    [
                        'route' => 'chronicle_show_by_Year_Slug',
                        'routeParameters' => ['year' => $year, 'slug' => $event['slug']]
                    ]
                );
            }
        }

        //Events, all sections
        $menu->addChild($this->translator->trans('menu.plan'),
            [
                'route' => 'plan',
                'attributes' => [
                    'dropdown' => 'true',
                ]
            ]
        );

        foreach ($cachedData['Plán'] as $year) {
            $menu[$this->translator->trans('menu.plan')]->addChild($year, [
                'route' => 'plan_show_by_Year',
                'routeParameters' => ['year' => $year]
            ]);
        }
          
        //Blog, all articles in all sections
        foreach ($this->getBlogSections($this->translator) as $blogSection) {
            $menu->addChild($blogSection[0], [
                'route' => 'blog_list_by_BlogSectionSlug',
                'routeParameters' => ['blogSectionSlug' => $blogSection[1]],
                'attributes' => [
                    'shortText' => $blogSection[2],
                ],
            ]);
            $menu[$blogSection[0]]->setDisplayChildren(false);

            foreach ($cachedData[$blogSection[0]] as $blog) {
                $menu[$blogSection[0]]->addChild($blog['title'], [
                    'route' => 'blog_show_by_BlogSectionSlug_Year_Slug',
                    'routeParameters' => [
                        'blogSectionSlug' => $blogSection[1],
                        'year' => $blog['year'],
                        'slug' => $blog['slug']
                    ]
                ]);
            }
        }

        $menu->addChild(
            $this->translator->trans('menu.contact'),
            ['route' => 'contact']
        );

        return $menu;
    }

    /**
     * Get data from DB or from second level cache
     * @return mixed $data []
     * @throws InvalidArgumentException
     */
    private function getData(): mixed
    {

        $cache = SecondLevelCachePDO::getInstance()->getCache();
        $doctrine = $this->doctrine;
        $translator = $this->translator;

        return $cache->get('main-menu-data', function (CacheItemInterface $item) use ($doctrine, $translator) {
            $data = [];

            //Invitation
            $invitationList = $doctrine->getRepository(EventInvitation::class)->findBy(
                ['publish' => 1],
                ['startDate' => 'DESC']
            );
            $data[$translator->trans('menu.invitations')] = $this->addEventsToYears($invitationList);

            //Chronicle
            $chronicleList = $doctrine->getRepository(EventChronicle::class)->findBy(
                ['publish' => 1],
                ['startDate' => 'DESC']
            );
            $data[$translator->trans('menu.chronicle')] = $this->addEventsToYears($chronicleList);

            //Plan (Event)
            $planYears = $doctrine->getRepository(Event::class)->findUniqueYears();
            foreach ($planYears as $year) {
                $data[$translator->trans('menu.plan')][] = $year;
            }

            //Blogs
            $blogSections = $this->getBlogSections($translator);
             foreach ($blogSections as $blogSection) {

                $idSection1 = $doctrine->getRepository(BlogSection::class)->findBySlug($blogSection[1]);
                $blogs = $doctrine->getRepository(Blog::class)->findAllByBlogSectionId($idSection1->getId());

                $i = 0;
                foreach ($blogs as $blog) {
                    $data[$blogSection[0]][$i]['title'] = $blog['title'];
                    $data[$blogSection[0]][$i]['slug'] = $blog['slug'];
                    $data[$blogSection[0]][$i]['year'] = $blog['createdAt']->format('Y');
                    $i++;
                }
            }

            return $data;
        });
    }

    /** Move events from simple object list to multidimensional array according to star date */
    private function addEventsToYears(array $events): array
    {
        $data = [];
        $i = 0;
        foreach ($events as $event) {
            $eventYear = $event->getStartDate()->format('Y');
            $data[$eventYear][$i]['title'] = $event->getTitle();
            $data[$eventYear][$i]['slug'] = $event->getSlug();
            $i++;
        }
        return $data;
    }

    private function getBlogSections(TranslatorInterface $translator): array
    {
        return [
            [
                $translator->trans('menu.blog.sectionA.title'),
                $translator->trans('menu.blog.sectionA.slug'),
                $translator->trans('menu.blog.sectionA.shortcut')
            ],
            [
                $translator->trans('menu.blog.sectionB.title'),
                $translator->trans('menu.blog.sectionB.slug'),
                $translator->trans('menu.blog.sectionB.shortcut')
            ],
            [
                $translator->trans('menu.blog.sectionC.title'),
                $translator->trans('menu.blog.sectionC.slug'),
                $translator->trans('menu.blog.sectionC.shortcut'),
            ]
        ];
    }
}
