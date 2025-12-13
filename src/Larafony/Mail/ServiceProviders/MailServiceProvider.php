<?php

declare(strict_types=1);

namespace Larafony\Framework\Mail\ServiceProviders;

use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Container\ServiceProvider;
use Larafony\Framework\Mail\Contracts\MailerContract;
use Larafony\Framework\Mail\Mailer;
use Larafony\Framework\Mail\MailerFactory;
use Larafony\Framework\View\ViewManager;
use Larafony\Framework\Web\Config;

class MailServiceProvider extends ServiceProvider
{
    /**
     * @return array<int|string, class-string>
     */
    public function providers(): array
    {
        return [MailerContract::class => Mailer::class];
    }

    public function boot(ContainerContract $container): void
    {
        parent::boot($container);

        /** @var ViewManager $viewManager */
        $viewManager = $container->get(ViewManager::class);

        $mailer = MailerFactory::createSmtpMailer(
            viewManager: $viewManager,
            host: Config::get('mail.host', 'localhost'),
            port: (int) Config::get('mail.port', 1025),
            username: Config::get('mail.username'),
            password: Config::get('mail.password'),
            encryption: Config::get('mail.encryption'),
        );

        $container->set(MailerContract::class, $mailer);
        $container->set(Mailer::class, $mailer);
    }
}
