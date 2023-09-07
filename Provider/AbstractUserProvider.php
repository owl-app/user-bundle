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

use Owl\Component\User\Canonicalizer\CanonicalizerInterface;
use Owl\Component\User\Model\PermissionUserInterface;
use Owl\Component\User\Model\UserInterface as SyliusUserInterface;
use Owl\Component\User\Repository\UserRepositoryInterface;
use Owl\Component\User\Security\Provider\PermissionProviderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

abstract class AbstractUserProvider implements UserProviderInterface
{
    /**
     * @param string $supportedUserClass FQCN
     */
    public function __construct(
        protected string $supportedUserClass,
        protected UserRepositoryInterface $userRepository,
        protected CanonicalizerInterface $canonicalizer,
        protected ?PermissionProviderInterface $permissionProvider = null
    ) {
        $this->supportedUserClass = $supportedUserClass;
        $this->userRepository = $userRepository;
        $this->canonicalizer = $canonicalizer;
        $this->permissionProvider = $permissionProvider;
    }

    public function loadUserByUsername(string $username): UserInterface
    {
        $username = $this->canonicalizer->canonicalize($username);
        $user = $this->findUser($username);

        if (null === $user) {
            throw new UserNotFoundException(
                sprintf('Username "%s" does not exist.', $username)
            );
        }

        return $user;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        return $this->loadUserByUsername($identifier);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof SyliusUserInterface) {
            throw new UnsupportedUserException(
                sprintf('User must implement "%s".', SyliusUserInterface::class)
            );
        }

        if (!$this->supportsClass(get_class($user))) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        /** @var UserInterface|null $reloadedUser */
        $reloadedUser = $this->userRepository->find($user->getId());
        if (null === $reloadedUser) {
            throw new UserNotFoundException(
                sprintf('User with ID "%d" could not be refreshed.', $user->getId())
            );
        }

        if ($reloadedUser instanceof PermissionUserInterface && $this->permissionProvider) {
            $reloadedUser->setPermissions(array_keys(
                $this->permissionProvider->getPermissionsByUserId($user->getId())
            ));
        }

        return $reloadedUser;
    }

    abstract protected function findUser(string $uniqueIdentifier): ?UserInterface;

    public function supportsClass(string $class): bool
    {
        return $this->supportedUserClass === $class || is_subclass_of($class, $this->supportedUserClass);
    }
}
