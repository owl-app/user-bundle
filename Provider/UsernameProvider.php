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

namespace Owl\Bundle\UserBundle\Provider;

use Symfony\Component\Security\Core\User\UserInterface;

class UsernameProvider extends AbstractUserProvider
{
    /**
     * @return \Owl\Component\User\Model\UserInterface|null
     *
     * @psalm-return T|null
     */
    protected function findUser(string $username): ?UserInterface
    {
        return $this->userRepository->findOneBy(['usernameCanonical' => $username]);
    }
}
