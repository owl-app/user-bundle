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

namespace spec\Owl\Bundle\UserBundle\Event;

use Owl\Component\User\Model\UserInterface;
use PhpSpec\ObjectBehavior;

final class UserEventSpec extends ObjectBehavior
{
    public function let(UserInterface $user): void
    {
        $this->beConstructedWith($user);
    }

    public function it_has_user(UserInterface $user): void
    {
        $this->getUser()->shouldReturn($user);
    }
}
