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

namespace Owl\Bundle\UserBundle\Security;

use Owl\Bundle\UserBundle\Event\UserEvent;
use Owl\Bundle\UserBundle\UserEvents;
use Owl\Component\User\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;
use Webmozart\Assert\Assert;

class UserLogin implements UserLoginInterface
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private UserCheckerInterface $userChecker,
        private EventDispatcherInterface $eventDispatcher,
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->userChecker = $userChecker;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function login(UserInterface $user, ?string $firewallName = null): void
    {
        $firewallName = $firewallName ?? 'main';

        Assert::isInstanceOf($user, SymfonyUserInterface::class);
        $this->userChecker->checkPreAuth($user);
        $this->userChecker->checkPostAuth($user);

        $token = $this->createToken($user, $firewallName);
        if (null === $token->getUser() || [] === $token->getUser()->getRoles()) {
            throw new AuthenticationException('Unauthenticated token');
        }

        $this->tokenStorage->setToken($token);
        $this->eventDispatcher->dispatch(new UserEvent($user), UserEvents::SECURITY_IMPLICIT_LOGIN);
    }

    protected function createToken(UserInterface $user, string $firewallName): UsernamePasswordToken
    {
        Assert::isInstanceOf($user, SymfonyUserInterface::class);

        return new UsernamePasswordToken(
            $user,
            $firewallName,
            array_map(/** @param object|string $role */ static function ($role): string { return (string) $role; }, $user->getRoles()),
        );
    }
}
