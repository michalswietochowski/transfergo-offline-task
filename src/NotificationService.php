<?php

namespace TransferGO;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Notifier\Event\SentMessageEvent;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;
use Symfony\Component\Notifier\Recipient\RecipientInterface;
use Symfony\Component\Notifier\Recipient\SmsRecipientInterface;

readonly class NotificationService
{
    public function __construct(
        private NotifierInterface $notifier,
        private LoggerInterface   $logger,
        EventDispatcherInterface  $eventDispatcher
    ) {
        $this->registerSentEventsLogging($eventDispatcher);
    }

    public function send(Notification $notification, RecipientInterface ...$recipients): void
    {
        $this->notifier->send($notification, ...$recipients);

        foreach ($recipients as $recipient) {
            $context = [
                'subject'  => $notification->getSubject(),
                'content'  => $notification->getContent(),
                'channels' => $notification->getChannels($recipient),
            ];
            if ($recipient instanceof SmsRecipientInterface) {
                $context['recipientPhone'] = $recipient->getPhone();
            }
            if ($recipient instanceof EmailRecipientInterface) {
                $context['recipientEmail'] = $recipient->getEmail();
            }

            $this->logger->info("Notification scheduled", $context);
        }
    }

    private function registerSentEventsLogging(EventDispatcherInterface $dispatcher): void
    {
        $dispatcher->addListener(SentMessageEvent::class, function (SentMessageEvent $event) {
            $message         = $event->getMessage();
            $originalMessage = $message->getOriginalMessage();

            $this->logger->info(
                "Notification sent",
                [
                    'id'          => $message->getMessageId(),
                    'transport'   => $message->getTransport(),
                    'subject'     => $originalMessage->getSubject(),
                    'recipientId' => $originalMessage->getRecipientId(),
                ]
            );
        });
    }
}
