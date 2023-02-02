# TransferGO Recruitment Offline Task

Create a service that accepts the necessary information and sends a notification to customers. It should provide an abstraction between at least two different messaging service providers.

It can use different messaging services/technologies for communication (e.g. SMS, email, push notification, Facebook Messenger etc).

If one of the services goes down, your service can quickly failover to a different provider without affecting your customers.

Example messaging providers:
* Emails: AWS SES (https://docs.aws.amazon.com/ses/latest/APIReference/API_SendEmail.html)
* SMS messages: Twilio (https://www.twilio.com/docs/sms/api)
* Push notifications: Pushy (https://pushy.me/docs/api/send-notifications)

All listed services are free to try and are pretty painless to sign up for, so please register your own test accounts on each.

Here is what we want to see in the service:
* Multi-channel: service can send messages via the multiple channels, with a fail-over
* Configuration-driven: It is possible to enable / disable different communication channels with configuration.
* (Bonus point) Localisation: service supports localised messages, in order for the customer to receive communication in their preferred language.
* (Bonus point) Usage tracking: we can track what messages were sent, when and to whom.
