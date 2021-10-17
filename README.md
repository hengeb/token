# Token

A small webtoken library for PHP

## Installation

Install the latest version with

```bash
$ composer require hengeb/token
```

## Example

```php
<?php

use Hengeb\Token\Token;

// generate token for an email change. The token payload includes the new address so it has not to be stored on the server until it is confirmed.
// to verify the token was not used before, the user's old address is used as additional information to sign the token but not stored in the token
// (along with the key "very-secret" which obviously should be something more secret)
$token = Token::encode([$user->id, $newEmail, time()], $user->email, "very-secret");

// it is save to use the token in a URL or database query, there is no need for escaping like urlencode()
mail($user->email, "confirm email", "Go to https://example.com/change-email.php?t=$token within 30 minutes to confirm your new email address.");

echo "Please check you postbox to confirm your new address.";
exit;

// [...]

// in change-email.php
try {
    // the callback function shall return the info that was used to create the token (in this case: the old email address)
    // it can also perform other checks to see if the token is valid
    $payload = Token::decode($_GET['t'], function ($payload) use (&$user) {
        list($userId, $email, $time) = $payload;
        if ($time < time() - 1800) {
            throw new \Exception("expired");
        }
        $user = loadUserFromDatabase($userId);
        // Return the information that was used to create the token.
        // this will also throw an exception if the user entry does not exist.
        return $user->email;
    }, "very-secret");
} catch (\RuntimeException $e) {
    die($e->getMessage() === "Invalid token: expired" ? "The confirmation link has expired, please request a new one." : "The confirmation link is invalid. Maybe you have already used it.");
}

$oldEmail = $user->email;
$user->email = $payload[1];
saveUser($user);
mail($oldEmail, "email changed", "You have successfully updated your email address to {$user->email}. This is our last mail to your old address.");

```

### Author

Henrik Gebauer - <code@henrik-gebauer.de> - <https://www.henrik-gebauer.de>

### License

This software is licensed under the MIT License - see the [LICENSE](LICENSE) file for details
