<?php

declare(strict_types=1);

namespace Owl\Bundle\UserBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class RegisterPermissionProviderPass implements CompilerPassInterface
{
    /**
     * @throws \InvalidArgumentException
     */
    public function process(ContainerBuilder $container): void
    {
        $userPermissionProviders = (array) $container->getParameter('owl.user_permission_providers');
        $users = (array) $container->getParameter(('owl.user.users'));

        if (empty($userPermissionProviders)) {
            return;
        }

        $permissionProviders = [];

        foreach ($container->findTaggedServiceIds('user_permission_provider') as $id => $attributes) {
            foreach ($attributes as $attribute) {
                if (!isset($attribute['alias'])) {
                    throw new \InvalidArgumentException('Tagged permission provider need to have `alias` attribute.');
                }
                $permissionProviders[$attribute['alias']] = $id;
            }
        }

        foreach ($userPermissionProviders as $userType => $provider) {
            if (!isset($users[$userType ])) {
                throw new \InvalidArgumentException(sprintf('User %s not exists.', $userType));
            }
            $abstractProviderService = $container->findDefinition(sprintf('owl.%s_user_provider', $userType));

            if (!isset($permissionProviders[$provider])) {
                throw new \InvalidArgumentException(sprintf('User permission provider %s not exists.', $provider));
            }

            $abstractProviderService->addArgument(new Reference($permissionProviders[$provider]));
        }
    }
}
