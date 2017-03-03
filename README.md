MailUp Rest Client
==================

[![Build Status](https://travis-ci.org/fazland/mailup-rest-client.svg?branch=master)](https://travis-ci.org/fazland/mailup-rest-client) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/fazland/mailup-rest-client/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/fazland/mailup-rest-client/?branch=master) [![Code Coverage](https://scrutinizer-ci.com/g/fazland/mailup-rest-client/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/fazland/mailup-rest-client/?branch=master)

Fazland's MailUp Rest Client is an unofficial PHP Rest Client for the Email and SMS GatewayProvider [MailUp](http://www.mailup.com). 

Requirements
------------
- php >= 7.0
- php-http/client-implementation >= 1.0
- php-http/discovery >= 1.0
- php-http/message >= 1.0
- php-http/message-factory >= 1.0
- psr/http-message >= 1.0
- psr/http-message-implementation >= 1.0
- symfony/options-resolver >= 2.7

Installation
------------
The suggested installation method is via [composer](https://getcomposer.org/):

```sh
$ composer require fazland/mailup-rest-client
```

Using MailUp Rest Client
------------------------
It's really simple. First of all, configuration!

### Configuration
The mandatory configuration parameters are:
- `username`
- `password`
- `client_id`
- `client_secret`

The only optional parameter is `cache_dir`. If set, the access token are saved in that path.

Just create a `Context` object passing to the constructor the parameters as an array:

```php
use Fazland\MailUpRestClient\Context;

$config = [
    'username' => 'your_username',
    'password' => 'your_password',
    'client_id' => 'your_client_id',
    'client_secret' => 'your_client_secret',
    'cache_dir' => 'path_to_your_cache_dir', // Optional
];

$httpClient = new HttpClientImplementation();

$context = new Context($config, $httpClient);
```

### Mailing Lists
To create a `MailingList` you can follow this example. Please, refer to the [MailUp official API docs](http://help.mailup.com/display/mailupapi/Manage+Lists+and+Groups#ManageListsandGroups-CreateList) for the `$params` array.

```php
use Fazland\MailUpRestClient\MailingList;

$email = "owner_of_the_list@email.com";
$params = [
    // your params...
];

$list = MailingList::create($context, $email, $params);
```

You can also obtain all the existing lists in your MailUp account by calling the static method `MailingList::getAll()`:

```php
use Fazland\MailUpRestClient\MailingList;

$lists = MailingList::getAll($context);
```

Once you have an instance of `MailingList`, you can do the following operations:
- add a `Recipient`
```php
use Fazland\MailUpRestClient\Recipient;

$list->addRecipient(new Recipient('Aragorn', 'aragorn@gondor.com', '3333333333', '+39'));
```
- update a `Recipient`
```php
use Fazland\MailUpRestClient\Recipient;

$list->updateRecipient(new Recipient('Aragorn', 'aragorn@gondor.com', '3334444444', '+39'));
```
- remove a `Recipient`
```php
use Fazland\MailUpRestClient\Recipient;

$list->removeRecipient(new Recipient('Aragorn', 'aragorn@gondor.com', '3333333333', '+39'));
```
- find a `Recipient` by its `email`
```php
$recipient = $list->findRecipient('aragorn@gondor.com'); // null returned if current email was not found
```
- retrieve all the groups of the current list:
```php
$groups = $list->getGroups();
```
- least, but not last, import an array of `Recipient` objects:
```php
$list->import($recipients);
```

### Groups

...

Contributing
------------
Contributions are welcome. Feel free to open a PR or file an issue here on GitHub!

License
-------
MailUp Rest Client is licensed under the MIT License - see the [LICENSE](https://github.com/fazland/mailup-rest-client/blob/master/LICENSE) file for details
