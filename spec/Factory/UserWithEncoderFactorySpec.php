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

namespace spec\Owl\Bundle\UserBundle\Factory;

use Owl\Component\User\Model\UserInterface;
use PhpSpec\ObjectBehavior;
use Sylius\Component\Resource\Factory\FactoryInterface;

final class UserWithEncoderFactorySpec extends ObjectBehavior
{
    public function let(FactoryInterface $decoratedUserFactory)
    {
        $this->beConstructedWith($decoratedUserFactory, 'encodername');
    }

    public function it_is_a_factory(): void
    {
        $this->shouldHaveType(FactoryInterface::class);
    }

    public function it_sets_the_given_encoder_name_on_created_user(FactoryInterface $decoratedUserFactory, UserInterface $user): void
    {
        $decoratedUserFactory->createNew()->willReturn($user);

        $user->setEncoderName('encodername')->shouldBeCalled();

        $this->createNew()->shouldReturn($user);
    }
}
