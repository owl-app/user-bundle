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

namespace Owl\Bundle\UserBundle\Reloader;

use Doctrine\Persistence\ObjectManager;
use Owl\Component\User\Model\UserInterface;

final class UserReloader implements UserReloaderInterface
{
    /** @var ObjectManager */
    private $objectManager;

    public function __construct(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function reloadUser(UserInterface $user): void
    {
        $this->objectManager->refresh($user);
    }
}
