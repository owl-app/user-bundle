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

namespace Owl\Bundle\UserBundle\Form\Type;

use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;

final class UserResetPasswordType extends AbstractResourceType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => ['label' => 'sylius.form.user.password.label'],
                'second_options' => ['label' => 'sylius.form.user.password.confirmation'],
                'invalid_message' => 'sylius.user.plainPassword.mismatch',
            ])
        ;
    }

    /**
     * @psalm-return 'sylius_user_reset_password'
     */
    public function getBlockPrefix(): string
    {
        return 'sylius_user_reset_password';
    }
}
