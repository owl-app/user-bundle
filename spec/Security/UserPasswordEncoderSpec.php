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

namespace spec\Owl\Bundle\UserBundle\Security;

use Owl\Component\User\Model\CredentialsHolderInterface;
use Owl\Component\User\Security\UserPasswordEncoderInterface;
use PhpSpec\ObjectBehavior;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

final class UserPasswordEncoderSpec extends ObjectBehavior
{
    public function let(EncoderFactoryInterface $encoderFactory): void
    {
        $this->beConstructedWith($encoderFactory);
    }

    public function it_implements_password_updater_interface(): void
    {
        $this->shouldImplement(UserPasswordEncoderInterface::class);
    }

    public function it_encodes_password(
        EncoderFactoryInterface $encoderFactory,
        PasswordEncoderInterface $passwordEncoder,
        CredentialsHolderInterface $user,
    ): void {
        $user->getPlainPassword()->willReturn('topSecretPlainPassword');
        $user->getSalt()->willReturn('typicalSalt');
        $encoderFactory->getEncoder($user->getWrappedObject())->willReturn($passwordEncoder);
        $passwordEncoder->encodePassword('topSecretPlainPassword', 'typicalSalt')->willReturn('topSecretEncodedPassword');

        $this->encode($user)->shouldReturn('topSecretEncodedPassword');
    }
}
