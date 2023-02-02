<?php

namespace TransferGO\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;
use TransferGO\Notification\TranslatableNotification;
use TransferGO\NotificationService;
use TransferGO\Recipient\Recipient;

#[AsCommand(
    name: 'transfergo:notification-test',
    description: 'Add a short description for your command',
)]
class NotificationTestCommand extends Command
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly TranslatorInterface $translator,
        string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->addArgument('subject', InputArgument::REQUIRED, 'Notification subject');
        $this->addArgument('channels', InputArgument::IS_ARRAY, 'Channels slugs');
        $this->addOption('content', 'c', InputOption::VALUE_REQUIRED, 'Notification content (if other than subject)', '');
        $this->addOption('recipient-email', null, InputOption::VALUE_REQUIRED, 'Recipient email', '');
        $this->addOption('recipient-phone', null, InputOption::VALUE_REQUIRED, 'Recipient phone', '');
        $this->addOption('recipient-language', null, InputOption::VALUE_REQUIRED, 'Recipient language', 'en');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $subject = $input->getArgument('subject');

        $notification = new TranslatableNotification(
            $this->translator,
            $subject,
            channels: $input->getArgument('channels')
        );

        $content = $input->getOption('content');
        if ($content) {
            $notification->content($content);
        }

        $recipient = new Recipient(
            $input->getOption('recipient-language'),
            $input->getOption('recipient-email'),
            $input->getOption('recipient-phone')
        );

        $this->notificationService->send($notification, $recipient);

        $io->success('Notification sent!');

        return Command::SUCCESS;
    }
}
