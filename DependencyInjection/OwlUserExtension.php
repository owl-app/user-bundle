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

namespace Owl\Bundle\UserBundle\DependencyInjection;

use Owl\Bundle\UserBundle\EventListener\UpdateUserHasherListener;
use Owl\Bundle\UserBundle\EventListener\UserDeleteListener;
use Owl\Bundle\UserBundle\EventListener\UserLastLoginSubscriber;
use Owl\Bundle\UserBundle\EventListener\UserReloaderListener;
use Owl\Bundle\UserBundle\Factory\UserWithHasherFactory;
use Owl\Bundle\UserBundle\Provider\AbstractUserProvider;
use Owl\Bundle\UserBundle\Provider\EmailProvider;
use Owl\Bundle\UserBundle\Provider\UsernameOrEmailProvider;
use Owl\Bundle\UserBundle\Provider\UsernameProvider;
use Owl\Bundle\UserBundle\Reloader\UserReloader;
use Owl\Component\User\Security\Checker\TokenUniquenessChecker;
use Owl\Component\User\Security\Generator\UniquePinGenerator;
use Owl\Component\User\Security\Generator\UniqueTokenGenerator;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Security\Http\SecurityEvents;

final class OwlUserExtension extends AbstractResourceExtension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration([], $container), $configs);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $loader->load(sprintf('services/integrations/%s.xml', $config['driver']));

        $this->registerResources('owl', $config['driver'], $this->resolveResources($config['resources'], $container), $container);

        $loader->load('services.xml');

        $this->createServices($config['resources'], $container);
        $this->loadHashersAwareServices($config['hasher'], $config['resources'], $container);

        $container->setParameter('owl.user_permission_providers', $config['permission_provider']);
    }

    /**
     * @return array[]
     *
     * @psalm-return array<string, array>
     */
    private function resolveResources(array $resources, ContainerBuilder $container): array
    {
        $container->setParameter('owl.user.users', $resources);

        $resolvedResources = [];
        foreach ($resources as $variableName => $variableConfig) {
            foreach ($variableConfig as $resourceName => $resourceConfig) {
                if (is_array($resourceConfig)) {
                    $resolvedResources[$variableName . '_' . $resourceName] = $resourceConfig;
                }
            }
        }

        return $resolvedResources;
    }

    private function createServices(array $resources, ContainerBuilder $container): void
    {
        foreach ($resources as $userType => $config) {
            $userClass = $config['user']['classes']['model'];

            $this->createTokenGenerators($userType, $config['user'], $container);
            $this->createReloaders($userType, $container);
            $this->createLastLoginListeners($userType, $userClass, $container);
            $this->createProviders($userType, $userClass, $container);
            $this->createUserDeleteListeners($userType, $container);
        }
    }

    private function loadHashersAwareServices(?string $globalHasher, array $resources, ContainerBuilder $container): void
    {
        foreach ($resources as $userType => $config) {
            $hasher = $config['user']['hasher'] ?? $globalHasher;

            if (null === $hasher || false === $hasher) {
                continue;
            }

            $this->overwriteResourceFactoryWithHasherAwareFactory($container, $userType, $hasher);
            $this->registerUpdateUserHasherListener($container, $userType, $hasher, $config);
        }
    }

    private function createTokenGenerators(string $userType, array $config, ContainerBuilder $container): void
    {
        $this->createUniquenessCheckers($userType, $config, $container);

        $container->setDefinition(
            sprintf('owl.%s_user.token_generator.password_reset', $userType),
            $this->createTokenGeneratorDefinition(
                UniqueTokenGenerator::class,
                [
                    new Reference('sylius.random_generator'),
                    new Reference(sprintf('owl.%s_user.token_uniqueness_checker.password_reset', $userType)),
                    $config['resetting']['token']['length'],
                ],
            ),
        )->setPublic(true);

        $container->setDefinition(
            sprintf('owl.%s_user.pin_generator.password_reset', $userType),
            $this->createTokenGeneratorDefinition(
                UniquePinGenerator::class,
                [
                    new Reference('sylius.random_generator'),
                    new Reference(sprintf('owl.%s_user.pin_uniqueness_checker.password_reset', $userType)),
                    $config['resetting']['pin']['length'],
                ],
            ),
        )->setPublic(true);

        $container->setDefinition(
            sprintf('owl.%s_user.token_generator.email_verification', $userType),
            $this->createTokenGeneratorDefinition(
                UniqueTokenGenerator::class,
                [
                    new Reference('sylius.random_generator'),
                    new Reference(sprintf('owl.%s_user.token_uniqueness_checker.email_verification', $userType)),
                    $config['verification']['token']['length'],
                ],
            ),
        )->setPublic(true);
    }

    private function createTokenGeneratorDefinition(string $generatorClass, array $arguments): Definition
    {
        $generatorDefinition = new Definition($generatorClass);
        $generatorDefinition->setArguments($arguments);

        return $generatorDefinition;
    }

    private function createUniquenessCheckers(string $userType, array $config, ContainerBuilder $container): void
    {
        $repositoryServiceId = sprintf('owl.repository.%s_user', $userType);

        $resetPasswordTokenUniquenessCheckerDefinition = new Definition(TokenUniquenessChecker::class);
        $resetPasswordTokenUniquenessCheckerDefinition->addArgument(new Reference($repositoryServiceId));
        $resetPasswordTokenUniquenessCheckerDefinition->addArgument($config['resetting']['token']['field_name']);
        $container->setDefinition(
            sprintf('owl.%s_user.token_uniqueness_checker.password_reset', $userType),
            $resetPasswordTokenUniquenessCheckerDefinition,
        );

        $resetPasswordPinUniquenessCheckerDefinition = new Definition(TokenUniquenessChecker::class);
        $resetPasswordPinUniquenessCheckerDefinition->addArgument(new Reference($repositoryServiceId));
        $resetPasswordPinUniquenessCheckerDefinition->addArgument($config['resetting']['pin']['field_name']);
        $container->setDefinition(
            sprintf('owl.%s_user.pin_uniqueness_checker.password_reset', $userType),
            $resetPasswordPinUniquenessCheckerDefinition,
        );

        $emailVerificationTokenUniquenessCheckerDefinition = new Definition(TokenUniquenessChecker::class);
        $emailVerificationTokenUniquenessCheckerDefinition->addArgument(new Reference($repositoryServiceId));
        $emailVerificationTokenUniquenessCheckerDefinition->addArgument($config['verification']['token']['field_name']);
        $container->setDefinition(
            sprintf('owl.%s_user.token_uniqueness_checker.email_verification', $userType),
            $emailVerificationTokenUniquenessCheckerDefinition,
        );
    }

    private function createReloaders(string $userType, ContainerBuilder $container): void
    {
        $managerServiceId = sprintf('owl.manager.%s_user', $userType);
        $reloaderServiceId = sprintf('owl.%s_user.reloader', $userType);
        $reloaderListenerServiceId = sprintf('owl.listener.%s_user.reloader', $userType);

        $userReloaderDefinition = new Definition(UserReloader::class);
        $userReloaderDefinition->addArgument(new Reference($managerServiceId));
        $container->setDefinition($reloaderServiceId, $userReloaderDefinition);

        $userReloaderListenerDefinition = new Definition(UserReloaderListener::class);
        $userReloaderListenerDefinition->addArgument(new Reference($reloaderServiceId));
        $userReloaderListenerDefinition->addTag('kernel.event_listener', ['event' => sprintf('owl.%s_user.post_create', $userType), 'method' => 'reloadUser']);
        $userReloaderListenerDefinition->addTag('kernel.event_listener', ['event' => sprintf('owl.%s_user.post_update', $userType), 'method' => 'reloadUser']);
        $container->setDefinition($reloaderListenerServiceId, $userReloaderListenerDefinition);
    }

    private function createLastLoginListeners(string $userType, string $userClass, ContainerBuilder $container): void
    {
        $managerServiceId = sprintf('owl.manager.%s_user', $userType);
        $lastLoginListenerServiceId = sprintf('owl.listener.%s_user_last_login', $userType);

        $lastLoginListenerDefinition = new Definition(UserLastLoginSubscriber::class);
        $lastLoginListenerDefinition->setArguments([new Reference($managerServiceId), $userClass]);
        $lastLoginListenerDefinition->addTag('kernel.event_subscriber');
        $container->setDefinition($lastLoginListenerServiceId, $lastLoginListenerDefinition);
    }

    public function createUserDeleteListeners(string $userType, ContainerBuilder $container): void
    {
        $userDeleteListenerServiceId = sprintf('owl.listener.%s_user_delete', $userType);
        $userPreDeleteEventName = sprintf('owl.%s_user.pre_delete', $userType);

        $userDeleteListenerDefinition = new Definition(UserDeleteListener::class);
        $userDeleteListenerDefinition->addArgument(new Reference('security.token_storage'));
        $userDeleteListenerDefinition->addArgument(new Reference('request_stack'));
        $userDeleteListenerDefinition->addTag('kernel.event_listener', ['event' => $userPreDeleteEventName, 'method' => 'deleteUser']);
        $container->setDefinition($userDeleteListenerServiceId, $userDeleteListenerDefinition);
    }

    private function createProviders(string $userType, string $userModel, ContainerBuilder $container): void
    {
        $repositoryServiceId = sprintf('owl.repository.%s_user', $userType);
        $abstractProviderServiceId = sprintf('owl.%s_user_provider', $userType);
        $providerEmailBasedServiceId = sprintf('owl.%s_user_provider.email_based', $userType);
        $providerNameBasedServiceId = sprintf('owl.%s_user_provider.name_based', $userType);
        $providerEmailOrNameBasedServiceId = sprintf('owl.%s_user_provider.email_or_name_based', $userType);

        $abstractProviderDefinition = new Definition(AbstractUserProvider::class);
        $abstractProviderDefinition->setAbstract(true);
        $abstractProviderDefinition->setLazy(true);
        $abstractProviderDefinition->addArgument($userModel);
        $abstractProviderDefinition->addArgument(new Reference($repositoryServiceId));
        $abstractProviderDefinition->addArgument(new Reference('sylius.canonicalizer'));

        $container->setDefinition($abstractProviderServiceId, $abstractProviderDefinition);

        $emailBasedProviderDefinition = new ChildDefinition($abstractProviderServiceId);
        $emailBasedProviderDefinition->setClass(EmailProvider::class);
        $container->setDefinition($providerEmailBasedServiceId, $emailBasedProviderDefinition);

        $nameBasedProviderDefinition = new ChildDefinition($abstractProviderServiceId);
        $nameBasedProviderDefinition->setClass(UsernameProvider::class);
        $container->setDefinition($providerNameBasedServiceId, $nameBasedProviderDefinition);

        $emailOrNameBasedProviderDefinition = new ChildDefinition($abstractProviderServiceId);
        $emailOrNameBasedProviderDefinition->setClass(UsernameOrEmailProvider::class);
        $container->setDefinition($providerEmailOrNameBasedServiceId, $emailOrNameBasedProviderDefinition);
    }

    private function overwriteResourceFactoryWithHasherAwareFactory(ContainerBuilder $container, string $userType, string $hasher): void
    {
        $factoryServiceId = sprintf('owl.factory.%s_user', $userType);

        $factoryDefinition = new Definition(
            UserWithHasherFactory::class,
            [
                $container->getDefinition($factoryServiceId),
                $hasher,
            ],
        );
        $factoryDefinition->setPublic(true);

        $container->setDefinition($factoryServiceId, $factoryDefinition);
    }

    private function registerUpdateUserHasherListener(ContainerBuilder $container, string $userType, string $hasher, array $resourceConfig): void
    {
        $updateUserHasherListenerDefinition = new Definition(UpdateUserHasherListener::class, [
            new Reference(sprintf('owl.manager.%s_user', $userType)),
            $hasher,
            $resourceConfig['user']['classes']['model'],
            $resourceConfig['user']['classes']['interface'],
            '_password',
        ]);
        $updateUserHasherListenerDefinition->addTag('kernel.event_listener', ['event' => SecurityEvents::INTERACTIVE_LOGIN]);

        $container->setDefinition(
            sprintf('owl.%s_user.listener.update_user_hasher', $userType),
            $updateUserHasherListenerDefinition,
        );
    }
}
