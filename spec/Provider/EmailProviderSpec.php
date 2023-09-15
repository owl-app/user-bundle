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

namespace spec\Owl\Bundle\UserBundle\Provider;

use Owl\Bundle\UserBundle\Provider\AbstractUserProvider;
use Owl\Component\User\Canonicalizer\CanonicalizerInterface;
use Owl\Component\User\Model\User;
use Owl\Component\User\Repository\UserRepositoryInterface;
use PhpSpec\ObjectBehavior;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class EmailProviderSpec extends ObjectBehavior
{
    public function let(UserRepositoryInterface $userRepository, CanonicalizerInterface $canonicalizer): void
    {
        $this->beConstructedWith(User::class, $userRepository, $canonicalizer);
    }

    public function it_implements_symfony_user_provider_interface(): void
    {
        $this->shouldImplement(UserProviderInterface::class);
    }

    public function it_should_extend_user_provider(): void
    {
        $this->shouldHaveType(AbstractUserProvider::class);
    }

    public function it_supports_sylius_user_model(): void
    {
        $this->supportsClass(User::class)->shouldReturn(true);
    }

    public function it_loads_user_by_email(
        UserRepositoryInterface $userRepository,
        CanonicalizerInterface $canonicalizer,
        User $user,
    ): void {
        $canonicalizer->canonicalize('test@user.com')->willReturn('test@user.com');

        $userRepository->findOneByEmail('test@user.com')->willReturn($user);

        $this->loadUserByUsername('test@user.com')->shouldReturn($user);
    }

    public function it_updates_user_by_user_name(UserRepositoryInterface $userRepository, User $user): void
    {
        $userRepository->find(1)->willReturn($user);

        $user->getId()->willReturn(1);

        $this->refreshUser($user)->shouldReturn($user);
    }

    public function it_should_throw_exception_when_unsupported_user_is_used(
        UserInterface $user,
    ): void {
        $this->shouldThrow(UnsupportedUserException::class)->during('refreshUser', [$user]);
    }
}
