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

use Owl\Bundle\UserBundle\Reloader\UserReloaderInterface;
use Owl\Component\User\Model\UserInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Webmozart\Assert\Assert;

final class UserReloaderListener
{
    /** @var UserReloaderInterface */
    private $userReloader;

    public function __construct(UserReloaderInterface $userReloader)
    {
        $this->userReloader = $userReloader;
    }

    public function reloadUser(GenericEvent $event): void
    {
        $user = $event->getSubject();

        Assert::isInstanceOf($user, UserInterface::class);

        $this->userReloader->reloadUser($user);
    }
}
