<?php


namespace App\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @author tim <tim@systemli.org>
 */
class BeforeRequestListener implements EventSubscriberInterface
{
    /**
     * @var ObjectManager
     */
    protected $manager;
    /**
     * @var TokenStorageInterface
     */
    protected $storage;
    /**
     * @var Security
     */
    protected $security;

    /**
     * BeforeRequestListener constructor.
     * @param ObjectManager         $manager
     * @param TokenStorageInterface $storage
     * @param Security              $security
     */
    public function __construct(ObjectManager $manager, TokenStorageInterface $storage, Security $security)
    {
        $this->manager = $manager;
        $this->storage = $storage;
        $this->security = $security;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($user = $this->getUser()) {
            if (!$this->security->isGranted('ROLE_ADMIN')) {
                $filter = $this->manager->getFilters()->enable('domain_filter');
                $filter->setParameter('domainId', $user->getDomain()->getId());
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => array(array('onKernelRequest')),
        );
    }

    /**
     * @return null|object|string
     */
    private function getUser()
    {
        $token = $this->storage->getToken();

        if (!$token) {
            return null;
        }

        $user = $token->getUser();

        if (!($user instanceof UserInterface)) {
            return null;
        }

        return $user;
    }
}
