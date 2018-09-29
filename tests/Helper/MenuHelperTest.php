<?php

namespace App\Tests\Helper;

use App\Helper\MenuHelper;
use Knp\Menu\ItemInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @author doobry <doobry@systemli.org>
 */
class MenuHelperTest extends TestCase
{
    private $childs = [];

    public function testBuild()
    {
        $translator = $this->getMockBuilder(TranslatorInterface::class)
            ->disableOriginalConstructor()->getMock();

        $helper = new MenuHelper($translator);

        $menu = $this->getMockBuilder(ItemInterface::class)
            ->disableOriginalConstructor()->getMock();
        $menu->method('addChild')
            ->willReturnCallback(function ($child, $options) {
                $this->childs[$child] = $options;
            });

        $builtMenu = $helper->build([], $menu);

        self::assertEquals($menu, $builtMenu);

        $elements = [
            [
                'name' => 'test_example_text',
                'url' => 'test_example_url',
            ],
        ];

        $helper->build($elements, $menu);

        self::assertNotEmpty($this->childs);
        self::assertCount(1, $this->childs);

        $elements = [
            [
                'name' => 'test_example_submenu',
                'type' => 'dropdown',
            ],
            [
                'name' => 'test_example_submenu_item',
                'url' => 'test_example_submenu_item_url',
            ],
        ];

        $helper->build($elements, $menu);
        dump($this->childs);

        self::assertCount(3, $this->childs);
        self::assertArrayHasKey('caret', $this->childs['test_example_submenu']);
        self::assertArrayNotHasKey('caret', $this->childs['test_example_submenu_item']);

    }
}
