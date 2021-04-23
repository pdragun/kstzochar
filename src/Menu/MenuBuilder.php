<?php
namespace App\Menu;

use App\Utils\SecondLevelCachePDO;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Contracts\Cache\ItemInterface as CacheItemInterface;
use Doctrine\ORM\EntityManagerInterface as DoctrineItemInterface;

class MenuBuilder
{
    private $factory;
    private $entityManager;

    /**
     * Add any other dependency you need...
     */
    public function __construct(FactoryInterface $factory, DoctrineItemInterface $entityManager)
    {
        $this->factory = $factory;
        $this->entityManager = $entityManager;
    }

    public function createMainMenu(array $options): ItemInterface
    {

        $cachedData = $this->getData();

        $menu = $this->factory->createItem('Domov', ['route' => 'home_page']);
        $menu->setChildrenAttribute('class', 'navbar-nav mr-auto');

        $menu->addChild('Úvod', ['route' => 'home_page']);

        //Ivitation, all articles in all years
        $menu->addChild('Pozvánky', [
            'route' => 'invitation_show',
            'attributes' => [
                'dropdown' => 'true',
            ]
        ]);

        foreach( $cachedData['Pozvánky'] as $year => $eventList ) {
                
            $menu['Pozvánky']->addChild($year, [
                'route' => 'invitation_list_by_Year',
                'routeParameters' => ['year' => $year]
            ]);
            $menu['Pozvánky'][$year]->setDisplayChildren(false);

            foreach( $eventList as $event ) {

                $menu['Pozvánky'][$year]->addChild($event['title'], [
                    'route' => 'invitation_show_by_Year_by_Slug',
                    'routeParameters' => ['year' => $year, 'slug' => $event['slug']]
                ]);
            }
        }


        //Chronicle, all articles in all years
        $menu->addChild('Kronika', [
            'route' => 'chronicle_show',
            'attributes' => [
                'dropdown' => 'true',
            ]
        ]);

        foreach( $cachedData['Kronika'] as $year => $eventList ) {
                
            $menu['Kronika']->addChild($year, [
                'route' => 'chronicle_list_by_Year',
                'routeParameters' => ['year' => $year]
            ]);
            $menu['Kronika'][$year]->setDisplayChildren(false);

            foreach( $eventList as $event ) {

                $menu['Kronika'][$year]->addChild($event['title'], [
                    'route' => 'chronicle_show_by_Year_Slug',
                    'routeParameters' => ['year' => $year, 'slug' => $event['slug']]
                ]);
            }
        }

        //Events, all sections
        $menu->addChild('Plán', [
            'route' => 'plan',
            'attributes' => [
                'dropdown' => 'true',
            ]
        ]);

        foreach( $cachedData['Plán'] as $year ) {
            $menu['Plán']->addChild($year, [
                'route' => 'plan_show_by_Year',
                'routeParameters' => ['year' => $year]
            ]);
        }
          
        //Blog, all articles in all sections
        $blogSections = [
            ['Z klubovej kuchyne', 'z-klubovej-kuchyne', 'Klubové info'],
            ['Viacdňové akcie', 'viacdnove-akcie', 'Viacdňové'],
            ['Receptúry na túry', 'receptury-na-tury', 'Recepty'],
        ];

        foreach($blogSections as $blogSection) {

            $menu->addChild($blogSection[0], [
                'route' => 'blog_list_by_BlogSectionSlug',
                'routeParameters' => ['blogSectionSlug' => $blogSection[1]],
                'attributes' => [
                    'shortText' => $blogSection[2],
                ],
            ]);
            $menu[$blogSection[0]]->setDisplayChildren(false);

            foreach( $cachedData[$blogSection[0]] as $blog) {

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

        $menu->addChild('Kontakt', ['route' => 'contact']);
        return $menu;
    }


    /**
     * Get data from DB or from second level cache
     * 
     * @return $data []
     */
    private function getData() {

        $cache = SecondLevelCachePDO::getInstance()->getCache();
        $em = $this->entityManager;
 
        $cached = $cache->get('main-menu-data', function (CacheItemInterface $item) use ($em) {
            $data = [];
      
            //Ivitation
            $invitationList = $em->getRepository(\App\Entity\EventInvitation::class)->findBy(
                ['publish' => 1],
                ['startDate' => 'DESC']
            );
            $data['Pozvánky'] = $this->addEventsToYars($invitationList);
            
            //Chronicle
            $chronicleList = $em->getRepository(\App\Entity\EventChronicle::class)->findBy(
                ['publish' => 1],
                ['startDate' => 'DESC']
            );
            $data['Kronika'] = $this->addEventsToYars($chronicleList);

            //Plan (Event)
            $planYears = $em->getRepository(\App\Entity\Event::class)->findUniqueYears();
            foreach( $planYears as $year ) {
                $data['Plán'][] = $year;
            }
           
            //Blogs
            $blogSections = [
                ['Z klubovej kuchyne', 'z-klubovej-kuchyne', 'Klubové info'],
                ['Viacdňové akcie', 'viacdnove-akcie', 'Viacdňové'],
                ['Receptúry na túry', 'receptury-na-tury', 'Recepty'],
            ];
 
            foreach($blogSections as $blogSection) {

                $idSection1 = $em->getRepository(\App\Entity\BlogSection::class)->findBySlug($blogSection[1]);
                $blogs = $em->getRepository(\App\Entity\Blog::class)->findAllByBlogSectionId($idSection1->getId());
                
                $i = 0;
                foreach($blogs as $blog) {
                    $data[$blogSection[0]][$i]['title'] = $blog['title'];
                    $data[$blogSection[0]][$i]['slug'] = $blog['slug'];
                    $data[$blogSection[0]][$i]['year'] = date_format($blog['createdAt'], 'Y');
                    $i++;
                }
            }

            return $data;
        });
 
        return $cached;
    }


    /**
     * Move events from siple object list to multidimensional array according to star date
     * 
     * return $data []
     */
    private function addEventsToYars( $events ) {
        $data = [];
        $i = 0;
        foreach( $events as $event) {
            $eventYear = $event->getStartDate()->format('Y');
            $data[$eventYear][$i]['title'] = $event->getTitle();
            $data[$eventYear][$i]['slug'] = $event->getSlug();
            $i++;
        }
        return $data;
    }

}