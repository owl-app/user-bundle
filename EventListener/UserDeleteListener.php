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

use Owl\Component\User\Model\UserInterface;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Webmozart\Assert\Assert;

final class UserDeleteListener
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private SessionInterface|RequestStack $requestStackOrSession,
    ) {
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function deleteUser(ResourceControllerEvent $event): void
    {
        $user = $event->getSubject();

        Assert::isInstanceOf($user, UserInterface::class);

        if ($this->isTryingToDeleteLoggedInUser($user)) {
            $event->stopPropagation();
            $event->setErrorCode(Response::HTTP_UNPROCESSABLE_ENTITY);
            $event->setMessage('Cannot remove currently logged in user.');

            if ($this->requestStackOrSession instanceof SessionInterface) {
                $session = $this->requestStackOrSession;
            } else {
                $session = $this->requestStackOrSession->getSession();
            }

            /** @var FlashBagInterface $flashBag */
            $flashBag = $session->getBag('flashes');
            $flashBag->add('error', 'Cannot remove currently logged in user.');
        }
    }

    private function isTryingToDeleteLoggedInUser(UserInterface $user): bool
    {
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return false;
        }

        $loggedUser = $token->getUser();
        if (!$loggedUser) {
            return false;
        }

        /** @var UserInterface $loggedUser */
        return $loggedUser->getId() === $user->getId() && $loggedUser->getRoles() === $user->getRoles();
    }
}
