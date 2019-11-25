<?php

namespace App\Tests\Builder;

use App\Builder\MenuBuilder;
use App\Enum\Roles;
use App\Helper\MenuHelper;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class MenuBuilderTest extends TestCase
{
    private $menu;
    private $children = [];
    private $factory;
    private $menuHelper;

    public function setUp()
    {
        $this->menu = $this->getMockBuilder(ItemInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->menu->method('addChild')
            ->willReturnCallback(function ($child, $options) {
                $this->children[$child] = $options;
            });

        $this->factory = $this->getMockBuilder(FactoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->factory->method('createItem')
            ->willReturn($this->menu);

        $this->menuHelper = $this->getMockBuilder(MenuHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->menuHelper->method('build')
            ->willReturnCallback(function ($elements, $menu) {
                $this->children = $elements;
            });
    }

    public function testCreateNavbarLeftEmpty()
    {
        $authChecker = $this->getMockBuilder(AuthorizationCheckerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $builder = new MenuBuilder($this->factory, $authChecker, $this->menuHelper, []);

        $builder->createNavbarLeft();

        self::assertCount(0, $this->children);
    }

    public function testCreateNavbarLeftNonEmpty()
    {
        $authChecker = $this->getMockBuilder(AuthorizationCheckerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $builder = new MenuBuilder($this->factory, $authChecker, $this->menuHelper, ['example', 'test']);

        $builder->createNavbarLeft();

        self::assertCount(2, $this->children);
    }

    public function testCreateNavbarRightAnonymous()
    {
        $authChecker = $this->getMockBuilder(AuthorizationCheckerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $authChecker->method('isGranted')
            ->willReturnMap([
                ['IS_AUTHENTICATED_FULLY', null, false],
                [Roles::DOMAIN_ADMIN, null, false],
            ]);
        $builder = new MenuBuilder($this->factory, $authChecker, $this->menuHelper, []);

        $builtMenu = $builder->createNavbarRight();

        self::assertCount(1, $this->children);
        self::assertArrayHasKey('navbar_right.login', $this->children);
        self::assertArrayNotHasKey('navbar_right.admin', $this->children);
        self::assertArrayNotHasKey('navbar_right.logout', $this->children);
    }

    public function testCreateNavbarRightUser()
    {
        $authChecker = $this->getMockBuilder(AuthorizationCheckerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $authChecker->method('isGranted')
            ->willReturnMap([
                ['IS_AUTHENTICATED_FULLY', null, true],
                [Roles::DOMAIN_ADMIN, null, false],
            ]);
        $builder = new MenuBuilder($this->factory, $authChecker, $this->menuHelper, []);

        $builtMenu = $builder->createNavbarRight();

        self::assertCount(1, $this->children);
        self::assertArrayNotHasKey('navbar_right.login', $this->children);
        self::assertArrayNotHasKey('navbar_right.admin', $this->children);
        self::assertArrayHasKey('navbar_right.logout', $this->children);
    }

    public function testCreateNavbarRightAdmin()
    {
        $authChecker = $this->getMockBuilder(AuthorizationCheckerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $authChecker->method('isGranted')
            ->willReturnMap([
                ['IS_AUTHENTICATED_FULLY', null, true],
                [Roles::DOMAIN_ADMIN, null, true],
            ]);
        $builder = new MenuBuilder($this->factory, $authChecker, $this->menuHelper, []);

        $builtMenu = $builder->createNavbarRight();

        self::assertCount(2, $this->children);
        self::assertArrayNotHasKey('navbar_right.login', $this->children);
        self::assertArrayHasKey('navbar_right.admin', $this->children);
        self::assertArrayHasKey('navbar_right.logout', $this->children);
    }
}
