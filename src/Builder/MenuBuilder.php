<?php

namespace App\Builder;

use App\Enum\Roles;
use App\Helper\MenuHelper;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class MenuBuilder
{
    /**
     * @var FactoryInterface
     */
    private $factory;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authChecker;
    /**
     * @var MenuHelper
     */
    private $menuHelper;
    /**
     * @var array
     */
    private $navbarLeft;

    /**
     * Constructor.
     */
    public function __construct(FactoryInterface $factory, AuthorizationCheckerInterface $authChecker, MenuHelper $menuHelper, array $navbarLeft)
    {
        $this->factory = $factory;
        $this->authChecker = $authChecker;
        $this->menuHelper = $menuHelper;
        $this->navbarLeft = $navbarLeft;
    }

    public function createNavbarLeft(): ItemInterface
    {
        $menu = $this->factory->createItem('root', ['navbar' => true]);

        if (!empty($this->navbarLeft)) {
            $menu = $this->menuHelper->build($this->navbarLeft, $menu);
        }

        return $menu;
    }

    public function createNavbarRight(): ItemInterface
    {
        $menu = $this->factory->createItem('root');
        $menu->setChildrenAttribute('class', 'nav navbar-nav navbar-right');

        if (!$this->authChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $menu->addChild('navbar_right.login', ['route' => 'login']);
        } else {
            if ($this->authChecker->isGranted(Roles::DOMAIN_ADMIN)) {
                $menu->addChild('navbar_right.admin', ['route' => 'sonata_admin_dashboard']);
            }

            $menu->addChild('navbar_right.logout', ['route' => 'logout']);
        }

        return $menu;
    }
}
