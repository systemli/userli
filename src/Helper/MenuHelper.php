<?php

namespace App\Helper;

use Knp\Menu\ItemInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class MenuHelper.
 */
class MenuHelper
{
    /**
     * Constructor.
     */
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function build(array $elements, ItemInterface $menu): ItemInterface
    {
        foreach ($elements as $item) {
            if (isset($item['name'])) {
                if (isset($item['type']) && 'dropdown' === $item['type']) {
                    $menu->addChild($item['name'], ['dropdown' => true, 'caret' => true]);
                    if (isset($item['items'])) {
                        $this->build($item['items'], $menu[$item['name']]);
                    }
                } elseif (isset($item['url'])) {
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

        return $menu;
    }
}
