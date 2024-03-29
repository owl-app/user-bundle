<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Owl\Bundle\UserBundle\EventListener;

use Doctrine\Persistence\ObjectManager;
use Owl\Bundle\UserBundle\Event\UserEvent;
use Owl\Bundle\UserBundle\UserEvents;
use Owl\Component\User\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

final class UserLastLoginSubscriber implements EventSubscriberInterface
{
    /** @var ObjectManager */
    private $userManager;

    /** @var string */
    private $userClass;

    public function __construct(ObjectManager $userManager, string $userClass)
    {
        $this->userManager = $userManager;
        $this->userClass = $userClass;
    }

    /**
     * @return string[]
     *
     * @psalm-return array{'security.interactive_login': 'onSecurityInteractiveLogin', 'sylius.user.security.implicit_login': 'onImplicitLogin'}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin',
            UserEvents::SECURITY_IMPLICIT_LOGIN => 'onImplicitLogin',
        ];
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $this->updateUserLastLogin($event->getAuthenticationToken()->getUser());
    }

    public function onImplicitLogin(UserEvent $event): void
    {
        $this->updateUserLastLogin($event->getUser());
    }

    private function updateUserLastLogin(\Symfony\Component\Security\Core\User\UserInterface|null $user): void
    {
        if (!$user instanceof $this->userClass) {
            return;
        }

        if (!$user instanceof UserInterface) {
            throw new \UnexpectedValueException('In order to use this subscriber, your class has to implement UserInterface');
        }

        $user->setLastLogin(new \DateTime());
        $this->userManager->persist($user);
        $this->userManager->flush();
    }
}
