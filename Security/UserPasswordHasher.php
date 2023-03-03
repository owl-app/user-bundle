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

use Owl\Component\User\Model\CredentialsHolderInterface;
use Owl\Component\User\Security\UserPasswordHasherInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class UserPasswordHasher implements UserPasswordHasherInterface
{
    /** @var PasswordHasherFactoryInterface */
    private $passwordHasher;

    public function __construct(PasswordHasherFactoryInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function hash(CredentialsHolderInterface $user): string
    {
        /** @psalm-suppress InvalidArgument */
        $hasher = $this->passwordHasher->getPasswordHasher($user);

        return $hasher->hash($user->getPlainPassword());
    }
}
