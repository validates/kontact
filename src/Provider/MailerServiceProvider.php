<?php

/*
 * This file is part of the fabschurt/kontact package.
 *
 * (c) 2016 Fabien Schurter <fabien@fabschurt.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FabSchurt\Kontact\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Application;
use Silex\Provider as SilexProvider;

/**
 * @author Fabien Schurter <fabien@fabschurt.com>
 */
final class MailerServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function register(Container $container)
    {
        // Register original provider
        if (!isset($container['mailer'])) {
            $container->register(new SilexProvider\SwiftmailerServiceProvider(), [
                'swiftmailer.options' => [
                    'host'       => $container['mailer.host'],
                    'port'       => $container['mailer.port'] ?: 25,
                    'username'   => $container['mailer.username'],
                    'password'   => $container['mailer.password'],
                    'encryption' => $container['mailer.encryption'],
                    'auth_mode'  => $container['mailer.auth_mode'],
                ],
            ]);
        }

        // Services
        $container['mailer.message.factory'] = $container->protect(
            /**
             * Returns a pre-configured `\Swift_Message` instance, drawing some
             * defaults from validated form data.
             *
             * @param array $data The data array from the validated form
             *
             * @return \Swift_Message
             */
            function (array $data) use ($container): \Swift_Message {
                return \Swift_Message::newInstance()
                    ->setSubject($container['mailer.message.subject'] ?: 'Kontact')
                    ->setFrom(
                        $data['address'] ?? $container['mailer.message.default_sender_address'] ?: $container['admin_email'],
                        $data['name'] ?? $container['mailer.message.default_sender_name']
                    )
                    ->setTo(
                        $container['mailer.message.recipient_address'] ?: $container['admin_email'],
                        $container['mailer.message.recipient_name']
                    )
                    ->setBody($container['twig']->render('message.txt.twig', $data))
                    ->setContentType('text/plain')
                ;
            }
        );
    }

    /**
     * {@inheritDoc}
     */
    public function boot(Application $app)
    {
        if ($app['debug']) {
            $app['mailer']->registerPlugin(new \Swift_Plugins_RedirectingPlugin($app['admin_email']));
        }
    }
}
