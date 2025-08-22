<?php
declare(strict_types=1);

namespace Hengeb\Token;

class Token
{
    private function __construct()
    {
    }

    /**
     * $info is some information that changes after the token has been used
     * @param mixed $payload
     */
    public static function encode($payload, string $info, string $secret): string
    {
        $str = rtrim(base64_encode(json_encode($payload)), '=');
        $sig = self::generateSignature($str, $info, $secret);
        return str_replace('=', '', strtr("$str:$sig", '+/', '-_')); // replace some characters so the token does not have to be urlencode'd
    }

    /**
     * @param $callback shall generate the exact $info that the token was created with if the token is valid
     *           it can also perform checks on the payload and throw an exception if it has become invalid
     * @throws \RuntimeException if the token is invalid
     * @return mixed
     */
    public static function decode(string $token, ?callable $callback, string $secret)
    {
        try {
            list($str, $sig) = explode(':', strtr($token, '-_', '+/'));
            $payload = json_decode(base64_decode($str), true);
            $info = ($callback === null) ? '' : $callback($payload);
            if ($sig !== self::generateSignature($str, $info, $secret)) {
                throw new \Exception('signature wrong');
            }
            return $payload;
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            if ($msg) {
                $msg = ": $msg";
            }
            throw new \RuntimeException('Invalid token' . $msg);
        }
    }

    private static function generateSignature($string, $info, $secret) {
        return rtrim(base64_encode(hash('sha256', $string . $info . $secret, true)), '=');
    }
}
