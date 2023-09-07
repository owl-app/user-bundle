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

namespace Owl\Bundle\UserBundle\Factory;

use Sylius\Component\Resource\Factory\FactoryInterface;
use Owl\Component\User\Model\UserInterface;
use Webmozart\Assert\Assert;

/**
 * @implements FactoryInterface<UserInterface>
 */
final class UserWithHasherFactory implements FactoryInterface
{
    /** @var FactoryInterface */
    private $decoratedUserFactory;

    /** @var string */
    private $hasherName;

    public function __construct(FactoryInterface $decoratedUserFactory, string $hasherName)
    {
        $this->decoratedUserFactory = $decoratedUserFactory;
        $this->hasherName = $hasherName;
    }

    public function createNew(): UserInterface
    {
        $user = $this->decoratedUserFactory->createNew();

        /** @var UserInterface $user */
        Assert::isInstanceOf($user, UserInterface::class);

        $user->setPasswordHasherName($this->hasherName);

        return $user;
    }
}
