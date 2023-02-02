<?php

namespace TransferGO\Tests\Notifier\Transport;

use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Notifier\Exception\TransportException;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Message\SentMessage;
use Symfony\Component\Notifier\Transport\TransportInterface;

class FailingTransport implements TransportInterface
{
    public function send(MessageInterface $message): SentMessage
    {
        throw new TransportException('Failing transport always fails', new MockResponse());
    }

    public function __toString(): string
    {
        return 'failing';
    }

    public function supports(MessageInterface $message): bool
    {
        return true;
    }
}
