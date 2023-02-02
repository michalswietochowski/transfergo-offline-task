# TransferGO Recruitment Offline Task

> Create a service that accepts the necessary information and sends a notification to customers. It should provide an abstraction between at least two different messaging service providers.
> 
> It can use different messaging services/technologies for communication (e.g. SMS, email, push notification, Facebook Messenger etc).
> 
> If one of the services goes down, your service can quickly failover to a different provider without affecting your customers.
> 
> Example messaging providers:
> * Emails: AWS SES (https://docs.aws.amazon.com/ses/latest/APIReference/API_SendEmail.html)
> * SMS messages: Twilio (https://www.twilio.com/docs/sms/api)
> * Push notifications: Pushy (https://pushy.me/docs/api/send-notifications)
> 
> All listed services are free to try and are pretty painless to sign up for, so please register your own test accounts on each.
> 
> Here is what we want to see in the service:
> * Multi-channel: service can send messages via the multiple channels, with a fail-over
> * Configuration-driven: It is possible to enable / disable different communication channels with configuration.
> * (Bonus point) Localisation: service supports localised messages, in order for the customer to receive communication in their preferred language.
> * (Bonus point) Usage tracking: we can track what messages were sent, when and to whom.

## Proposed solution

### Notification service class `\TransferGO\NotificationService`

I decided to use [`symfony/notifier`](https://symfony.com/doc/current/notifier.html) as a main library supporting this service class because:
* I didn't want to "reinvent the wheel" and implement these features myself. Also, I wouldn't be able to produce such robust solution in such short time.
* This component is stable, renowned and, a part of Symfony framework so integrates well. It has very good documentation and support of the community.
* Supports sending messages to multiple recipients via the multiple channels.
* Supports fail-over out-of-the box (at least on per-type level: sms, push, chat).
* It is configuration-driven and because I used micro Symfony skeleton app I didn't have to wire everything up by myself (loading env, configs, DI container) and testing was easier.
* It has multitude of ready-to-use provider adapters and registering new ones is very easy.
* Choice of channels for notifications can be configured by channels policies.

### Localisation support
* Developers can use `\TransferGO\Notification\TranslatableNotification` class which extends default notification class and requires passing `\Symfony\Contracts\Translation\TranslatorInterface` and optionally translation related parameters to the constructor.
* While used together with `\TransferGO\Recipient\Recipient` which implements `\Symfony\Contracts\Translation\LocaleAwareInterface` it allows to pass language codes together with recipient data like email, phone number.
* Translation is handled with `symfony/translation` component.

### Logging of sent notifications via `psr/log`
* Every notification is logged with `\Psr\Log\LogLevel::INFO` log level and notification and recipient metadata.
* Service also adds a listener for `\Symfony\Component\Notifier\Event\SentMessageEvent` to event dispatcher and logs IDs of sent messages.
* Logger (by default) adds time for each event.
* This way usage can be tracked.

### Channel Failover
* Developer can use [Failover or Round-Robin Transports](https://symfony.com/doc/current/notifier.html#configure-to-use-failover-or-round-robin-transports) provided by Symfony Notifier component.
* Transports are grouped by component into two groups: Chatters (Chat) and Texters (SMS, Push).
* Email channel uses Symfony Mailer and is a separate channel too.
* There is one limitation with failover mechanism here, because it works only and separately for Chatters transports and for Texters transports.
* Although in general we shouldn't test code that does not belong to us, here for testing purposes I created `\TransferGO\Tests\Notifier\Transport\FailingTransport` class which always throws an exception.

Examples:
* Falling back from Twilio to Amazon SNS or to Pushy as Text transport is supported.
* Falling back from Facebook Messenger to Slack as Chat transport is supported.
* Falling back from SMS to Chat or from SMS to Email is not supported.

## Usage

#### Sending emails only notifications

```php
<?php
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\Recipient;
use TransferGO\NotificationService;
// [...]
// Load service from DI container.
$notificationService = $container->get(NotificationService::class);

$notification = (new Notification('Test subject', ['email']))->content('Test message');

$recipient = new Recipient('someuser@example.org');

$notificationService->send($notification, $recipient);
```

#### Sending multi-channel notifications

```php
<?php
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\Recipient;
use TransferGO\NotificationService;
// [...]
// Load service from DI container.
$notificationService = $container->get(NotificationService::class);

// Multiple channels specified in notification.
$notification = (new Notification('Test subject', ['sms/test', 'chat/test']))->content('Test message');

// Recipient has both email and phone.
$recipient = new Recipient('someuser@example.org', '+48600123456');

$notificationService->send($notification, $recipient);
```

#### Sending localized notifications

```php
<?php
use Symfony\Contracts\Translation\TranslatorInterface;
use TransferGO\Notification\TranslatableNotification;
use TransferGO\NotificationService;
use TransferGO\Recipient\Recipient;
// [...]
// Load services from DI container.
$notificationService = $container->get(NotificationService::class);
$translator          = $container->get(TranslatorInterface::class);

$notification = (new TranslatableNotification($translator, 'Test subject', channels: ['email']))->content('Test message');

$polishRecipient  = new Recipient('pl', 'someuser@example.org');
$englishRecipient = new Recipient('lt', 'someotheruser@example.org');

// Multiple recipients as subsequent arguments.
$notificationService->send($notification, $polishRecipient, $englishRecipient);
```

## Testing

#### PHPUnit

Tests can be run by `bin/phpunit` command or while using provided Docker Compose service: `docker-compose run php bin/phpunit`.

#### CLI

Testing with real integrations can be done by:
1. Creating `.env.local` file in project root and providing configuration there:
```dotenv
MAILER_DSN=smtp://localhost:1025
TWILIO_DSN=twilio://SID:TOKEN@default?from=FROM
AMAZON_SNS_DSN=sns://ACCESS_ID:ACCESS_KEY@default?region=REGION
SLACK_DSN=slack://TOKEN@default?channel=CHANNEL
TELEGRAM_DSN=telegram://TOKEN@default?channel=CHAT_ID
```
2. Configuring notifier channels in `config/packages/notifier.yaml` config (or using the ones configured).
3. (Optional) Start Mailcatcher to test sending emails: `docker-compose up -d`. Mails can be seen at http://localhost:1080.
4. Running CLI command implemented in `\TransferGO\Command\NotificationTestCommand`:
```shell
bin/console transfergo:notification-test \
            --recipient-email "someuser@example.com" \
            --recipient-language "pl" \
            --recipient-phone "+48600123456" \
            --content "This is the content of the notification ❤️" \
            "This is subject of the notification" \
            email
            #can provide multiple channels after the space
```
