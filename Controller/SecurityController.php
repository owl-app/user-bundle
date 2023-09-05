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

namespace Owl\Bundle\UserBundle\Controller;

use Owl\Bundle\UserBundle\Form\Type\UserLoginType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Webmozart\Assert\Assert;

class SecurityController extends AbstractController
{
    private AuthenticationUtils $authenticationUtils;

    private FormFactoryInterface $formFactory;

    public function __construct(AuthenticationUtils $authenticationUtils, FormFactoryInterface $formFactory)
    {
        $this->authenticationUtils = $authenticationUtils;
        $this->formFactory = $formFactory;
    }

    /**
     * Login form action.
     */
    public function loginAction(Request $request): Response
    {
        $authenticationUtils = $this->authenticationUtils;
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        $options = $request->attributes->get('_sylius');

        $template = $options['template'] ?? null;
        Assert::notNull($template, 'Template is not configured.');

        $formType = $options['form'] ?? UserLoginType::class;
        $form = $this->formFactory->createNamed('', $formType);

        return $this->render($template, [
            'form' => $form->createView(),
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    /**
     * Login check action. This action should never be called.
     *
     * @return never
     */
    public function checkAction(Request $request)
    {
        throw new \RuntimeException('You must configure the check path to be handled by the firewall.');
    }

    /**
     * Logout action. This action should never be called.
     *
     * @return never
     */
    public function logoutAction(Request $request)
    {
        throw new \RuntimeException('You must configure the logout path to be handled by the firewall.');
    }
}
