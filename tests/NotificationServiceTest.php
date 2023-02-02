<?php

namespace TransferGO\Tests;

use Monolog\Handler\TestHandler;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Notifier\Event\SentMessageEvent;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\Recipient;
use Symfony\Contracts\Translation\TranslatorInterface;
use TransferGO\Notification\TranslatableNotification;
use TransferGO\NotificationService;
use TransferGO\Recipient\Recipient as LocaleAwareRecipient;

class NotificationServiceTest extends KernelTestCase
{
    public function testNotificationCanBeSentByEmail(): void
    {
        $container = static::getContainer();

        $notificationService = $container->get(NotificationService::class);

        $notification = (new Notification('Test subject', ['email']))->content('Test message');

        $recipient = new Recipient('someuser@example.org');

        $notificationService->send($notification, $recipient);

        $email = $this->getMailerMessage();

        $this->assertEmailHeaderSame($email, 'Subject', 'Test subject');
        $this->assertEmailTextBodyContains($email, 'Test message');
    }

    public function testNotificationCanBeSentToMultipleChannels(): void
    {
        $container = static::getContainer();

        $notificationService = $container->get(NotificationService::class);
        $eventDispatcher     = $container->get(EventDispatcherInterface::class);

        /** @var array|SentMessageEvent[] $events */
        $events = [];

        $eventDispatcher->addListener(SentMessageEvent::class, function (SentMessageEvent $event) use (&$events) {
            $events[] = $event;
        });

        $notification = (new Notification('Test subject', ['sms/test', 'chat/test']))->content('Test message');

        $recipient = new Recipient('someuser@example.org', '+48600123456');

        $notificationService->send($notification, $recipient);

        $this->assertCount(2, $events);
    }

    public function testNotificationChannelsSupportFailover(): void
    {
        $container = static::getContainer();

        $notificationService = $container->get(NotificationService::class);
        $eventDispatcher     = $container->get(EventDispatcherInterface::class);

        /** @var array|SentMessageEvent[] $events */
        $events = [];

        $eventDispatcher->addListener(SentMessageEvent::class, function (SentMessageEvent $event) use (&$events) {
            $events[] = $event;
        });

        $notification = (new Notification('Test subject', ['sms/failover_test', 'chat/failover_test']))->content('Test message');

        $recipient = new Recipient('someuser@example.org', '+48600123456');

        $notificationService->send($notification, $recipient);

        $this->assertCount(2, $events);
    }

    public function testTranslatableNotificationCanBeUsed(): void
    {
        $container = static::getContainer();

        $notificationService = $container->get(NotificationService::class);
        $translator          = $container->get(TranslatorInterface::class);

        $notification = (new TranslatableNotification($translator, 'Test subject', channels: ['email']))->content('Test message');

        $polishRecipient  = new LocaleAwareRecipient('pl', 'someuser@example.org');
        $englishRecipient = new LocaleAwareRecipient('lt', 'someotheruser@example.org');

        $notificationService->send($notification, $polishRecipient, $englishRecipient);

        $emails = $this->getMailerMessages();

        $this->assertCount(2, $emails);
        $this->assertEmailHeaderSame($emails[0], 'Subject', 'Testowy temat');
        $this->assertEmailTextBodyContains($emails[0], 'Testowa wiadomość');
        $this->assertEmailHeaderSame($emails[1], 'Subject', 'Bandymo pranešimo tema');
        $this->assertEmailTextBodyContains($emails[1], 'Bandomasis pranešimas');
    }

    public function testNotificationsAreLogged(): void
    {
        $container = static::getContainer();

        $notificationService = $container->get(NotificationService::class);
        $logger     = $container->get(LoggerInterface::class);

        $notification = (new Notification('Test subject', ['chat/test']))->content('Test message');

        $recipient = new Recipient('someuser@example.org', '+48600123456');

        $notificationService->send($notification, $recipient);

        /** @var TestHandler $testLogHandler */
        $testLogHandler = $logger->getHandlers()[0];
        $this->assertCount(2, $testLogHandler->getRecords());
    }
}
