<?php

namespace TransferGO\Recipient;

use Symfony\Component\Notifier\Recipient\Recipient as NotifierRecipient;
use Symfony\Contracts\Translation\LocaleAwareInterface;

class Recipient extends NotifierRecipient implements LocaleAwareInterface
{
    public function __construct(private string $locale, string $email = '', string $phone = '')
    {
        parent::__construct($email, $phone);
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }
}
