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

namespace spec\Owl\Bundle\UserBundle\Reloader;

use Doctrine\Persistence\ObjectManager;
use Owl\Bundle\UserBundle\Reloader\UserReloaderInterface;
use Owl\Component\User\Model\UserInterface;
use PhpSpec\ObjectBehavior;

final class UserReloaderSpec extends ObjectBehavior
{
    public function let(ObjectManager $objectManager): void
    {
        $this->beConstructedWith($objectManager);
    }

    public function it_implements_user_reloader_interface(): void
    {
        $this->shouldImplement(UserReloaderInterface::class);
    }

    public function it_reloads_user(ObjectManager $objectManager, UserInterface $user): void
    {
        $objectManager->refresh($user)->shouldBeCalled();

        $this->reloadUser($user);
    }
}
