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
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
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
