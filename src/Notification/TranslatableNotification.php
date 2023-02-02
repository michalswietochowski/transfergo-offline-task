<?php

namespace TransferGO\Notification;

use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Message\PushMessage;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\Notification\ChatNotificationInterface;
use Symfony\Component\Notifier\Notification\EmailNotificationInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Notification\PushNotificationInterface;
use Symfony\Component\Notifier\Notification\SmsNotificationInterface;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;
use Symfony\Component\Notifier\Recipient\RecipientInterface;
use Symfony\Component\Notifier\Recipient\SmsRecipientInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class TranslatableNotification extends Notification implements
    ChatNotificationInterface,
    EmailNotificationInterface,
    SmsNotificationInterface,
    PushNotificationInterface
{
    private TranslatorInterface $translator;
    private array $subjectParameters;
    private array $contentParameters = [];
    private ?string $domain;

    public function __construct(
        TranslatorInterface $translator,
        string $subject = '',
        array $subjectParameters = [],
        array $channels = [],
        ?string $domain = null
    ) {
        parent::__construct($subject, $channels);

        $this->translator = $translator;
        $this->subjectParameters = $subjectParameters;
        $this->domain = $domain;
    }

    public function asChatMessage(RecipientInterface $recipient, string $transport = null): ?ChatMessage
    {
        return ChatMessage::fromNotification($this->translateNotification($recipient));
    }

    public function asEmailMessage(EmailRecipientInterface $recipient, string $transport = null): ?EmailMessage
    {
        return EmailMessage::fromNotification($this->translateNotification($recipient), $recipient);
    }

    public function asPushMessage(RecipientInterface $recipient, string $transport = null): ?PushMessage
    {
        return PushMessage::fromNotification($this->translateNotification($recipient));
    }

    public function asSmsMessage(SmsRecipientInterface $recipient, string $transport = null): ?SmsMessage
    {
        return SmsMessage::fromNotification($this->translateNotification($recipient), $recipient);
    }

    private function translateNotification(RecipientInterface $recipient): Notification
    {
        $subject = $this->getSubject();
        $content = $this->getContent();

        if ($recipient instanceof LocaleAwareInterface) {
            $locale = $recipient->getLocale();

            $subject = $this->translator->trans($subject, $this->subjectParameters, $this->domain, $locale);
            $content = $this->translator->trans($content, $this->contentParameters, $this->domain, $locale);
        }

        $notification = new Notification($subject, $this->getChannels($recipient));
        $notification->content($content);

        return $notification;
    }
}
