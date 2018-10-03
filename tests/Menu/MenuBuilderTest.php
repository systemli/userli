<?php

namespace App\Tests\Menu;

use App\Enum\Roles;
use App\Helper\MenuHelper;
use App\Menu\MenuBuilder;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;

/**
 * @author doobry <doobry@systemli.org>
 */
class MenuBuilderTest extends TestCase
{
    private $menu;
    private $childs = [];
    private $factory;
    private $menuHelper;

    public function setUp()
    {
        $this->menu = $this->getMockBuilder(ItemInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->menu->method('addChild')
            ->willReturnCallback(function ($child, $options) {
                $this->childs[$child] = $options;
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
                    $this->childs = $elements;
            });
    }

    public function testCreateNavbarLeftEmpty()
    {
        $authChecker = $this->getMockBuilder(AuthorizationCheckerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $builder = new MenuBuilder($this->factory, $authChecker, $this->menuHelper, []);

        $builder->createNavbarLeft();

        self::assertCount(0, $this->childs);
    }

    public function testCreateNavbarLeftNonEmpty()
    {
        $authChecker = $this->getMockBuilder(AuthorizationCheckerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $builder = new MenuBuilder($this->factory, $authChecker, $this->menuHelper, ['example', 'test']);

        $builder->createNavbarLeft();

        self::assertCount(2, $this->childs);
    }

    public function testCreateNavbarRightAnonymous()
    {
        $authChecker = $this->getMockBuilder(AuthorizationCheckerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $authChecker->method('isGranted')
            ->willReturnMap([
                ['IS_AUTHENTICATED_FULLY', null, False],
                [Roles::ADMIN, null, False],
            ]);
        $builder = new MenuBuilder($this->factory, $authChecker, $this->menuHelper, []);

        $builtMenu = $builder->createNavbarRight();

        self::assertCount(2, $this->childs);
        self::assertArrayHasKey('navbar_right.login', $this->childs);
        self::assertArrayHasKey('navbar_right.register', $this->childs);
        self::assertArrayNotHasKey('navbar_right.admin', $this->childs);
        self::assertArrayNotHasKey('navbar_right.logout', $this->childs);
    }

    public function testCreateNavbarRightUser()
    {
        $authChecker = $this->getMockBuilder(AuthorizationCheckerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $authChecker->method('isGranted')
            ->willReturnMap([
                ['IS_AUTHENTICATED_FULLY', null, True],
                [Roles::ADMIN, null, False],
            ]);
        $builder = new MenuBuilder($this->factory, $authChecker, $this->menuHelper, []);

        $builtMenu = $builder->createNavbarRight();

        self::assertCount(1, $this->childs);
        self::assertArrayNotHasKey('navbar_right.login', $this->childs);
        self::assertArrayNotHasKey('navbar_right.register', $this->childs);
        self::assertArrayNotHasKey('navbar_right.admin', $this->childs);
        self::assertArrayHasKey('navbar_right.logout', $this->childs);
    }

    public function testCreateNavbarRightAdmin()
    {
        $authChecker = $this->getMockBuilder(AuthorizationCheckerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $authChecker->method('isGranted')
            ->willReturnMap([
                ['IS_AUTHENTICATED_FULLY', null, True],
                [Roles::ADMIN, null, True],
            ]);
        $builder = new MenuBuilder($this->factory, $authChecker, $this->menuHelper, []);

        $builtMenu = $builder->createNavbarRight();

        self::assertCount(2, $this->childs);
        self::assertArrayNotHasKey('navbar_right.login', $this->childs);
        self::assertArrayNotHasKey('navbar_right.register', $this->childs);
        self::assertArrayHasKey('navbar_right.admin', $this->childs);
        self::assertArrayHasKey('navbar_right.logout', $this->childs);
    }
}
