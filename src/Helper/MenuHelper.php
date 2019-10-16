<?php

namespace App\Helper;

use Knp\Menu\ItemInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class MenuHelper.
 */
class MenuHelper
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * Constructor.
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param array         $elements
     * @param ItemInterface $menu
     *
     * @return ItemInterface
     */
    public function build(array $elements, ItemInterface $menu)
    {
        foreach ($elements as $item) {
            if (isset($item['name'])) {
                if (isset($item['type']) && 'dropdown' === $item['type']) {
                    $menu->addChild($item['name'], ['dropdown' => true, 'caret' => true]);
                    if (isset($item['items'])) {
                        $this->build($item['items'], $menu[$item['name']]);
                    }
                } else {
                    if (isset($item['url'])) {
                        $menu->addChild(
                            $item['name'],
                            [
                                'uri' => $this->translator->trans($item['url']),
                                'linkAttributes' => ['target' => '_blank'],
                            ]
                        );
                    }
                }
            }
        }

        return $menu;
    }
}
