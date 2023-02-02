<?php

namespace TransferGO\Tests\Notifier\Transport;

use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\Transport\AbstractTransportFactory;
use Symfony\Component\Notifier\Transport\Dsn;

final class FailingTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): FailingTransport
    {
        if ('failing' === $dsn->getScheme()) {
            return new FailingTransport();
        }

        throw new UnsupportedSchemeException($dsn, 'failing', $this->getSupportedSchemes());
    }

    protected function getSupportedSchemes(): array
    {
        return ['failing'];
    }
}
