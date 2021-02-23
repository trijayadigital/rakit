<?php

namespace System;

defined('DS') or exit('No direct script access.');

class Str
{
    public static $snake = [];
    public static $camel = [];
    public static $studly = [];

    private static $strings = [];

    /**
     * Hitung panjang string.
     *
     * @param string $value
     *
     * @return int
     */
    public static function length($value)
    {
        return mb_strlen($value, 'UTF-8');
    }

    public static function substr($string, $start, $length = null)
    {
        return mb_substr($string, $start, $length, 'UTF-8');
    }

    public static function ucfirst($string)
    {
        return static::upper(static::substr($string, 0, 1)).static::substr($string, 1);
    }

    public static function lower($value)
    {
        return mb_strtolower($value, 'UTF-8');
    }

    public static function upper($value)
    {
        return mb_strtoupper($value, 'UTF-8');
    }

    public static function title($value)
    {
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    public static function limit($value, $limit = 100, $end = '...')
    {
        if (mb_strwidth($value, 'UTF-8') <= $limit) {
            return $value;
        }

        return rtrim(mb_strimwidth($value, 0, $limit, '', 'UTF-8')).$end;
    }

    public static function words($value, $words = 100, $end = '...')
    {
        preg_match('/^\s*+(?:\S++\s*+){1,'.$words.'}/u', $value, $matches);

        if (! isset($matches[0]) || static::length($value) === static::length($matches[0])) {
            return $value;
        }

        return rtrim($matches[0]).$end;
    }

    public static function singular($string)
    {
        if (empty(static::$strings)) {
            static::$strings = Config::get('strings');
        }

        if (in_array(mb_strtolower($string, 'UTF-8'), static::$strings['uncountable'])) {
            return $string;
        }

        foreach (static::$strings['irregular'] as $result => $pattern) {
            $pattern = '/'.$pattern.'$/i';
            if (preg_match($pattern, $string)) {
                return preg_replace($pattern, $result, $string);
            }
        }

        foreach (static::$strings['singular'] as $pattern => $result) {
            if (preg_match($pattern, $string)) {
                return preg_replace($pattern, $result, $string);
            }
        }

        return $string;
    }

    public static function plural($string)
    {
        if (empty(static::$strings)) {
            static::$strings = Config::get('strings');
        }

        if (in_array(mb_strtolower($string, 'UTF-8'), static::$strings['uncountable'])) {
            return $string;
        }

        foreach (static::$strings['irregular'] as $pattern => $result) {
            $pattern = '/'.$pattern.'$/i';
            if (preg_match($pattern, $string)) {
                return preg_replace($pattern, $result, $string);
            }
        }

        foreach (static::$strings['plural'] as $pattern => $result) {
            if (preg_match($pattern, $string)) {
                return preg_replace($pattern, $result, $string);
            }
        }

        return $string;
    }

    /**
     * Pluralize the last word of an English, studly caps case string.
     *
     * @param string $value
     * @param int    $count
     *
     * @return string
     */
    public static function plural_studly($value, $count = 2)
    {
        $parts = preg_split('/(.)(?=[A-Z])/u', $value, -1, PREG_SPLIT_DELIM_CAPTURE);
        $last = array_pop($parts);

        return implode('', $parts).self::plural($last, $count);
    }

    public static function slug($value, $separator = '-')
    {
        $flip = ('-' === $separator) ? '_' : '-';
        $value = preg_replace('!['.preg_quote($flip).']+!u', $separator, $value);
        $value = str_replace('@', $separator.'at'.$separator, $value);
        $value = preg_replace('![^'.preg_quote($separator).'\pL\pN\s]+!u', '', static::lower($value));
        $value = preg_replace('!['.preg_quote($separator).'\s]+!u', $separator, $value);

        return trim($value, $separator);
    }

    public static function classify($value)
    {
        return str_replace(' ', '_', static::title(str_replace(['_', '-', '.', '/'], ' ', $value)));
    }

    public static function segments($value)
    {
        return array_diff(explode('/', trim($value, '/')), ['']);
    }

    public static function random($length = 16)
    {
        $string = '';

        while (($length2 = strlen($string)) < $length) {
            $size = $length - $length2;
            $bytes = base64_encode(static::bytes($size));
            $string .= substr(str_replace(['/', '+', '='], '', $bytes), 0, $size);
        }

        return $string;
    }

    /**
     * Generate cryptographically secure random bytes.
     *
     * @param int $length
     *
     * @return string
     */
    public static function bytes($length)
    {
        if (! is_int($length)) {
            throw new \InvalidArgumentException('Bytes length must be a positive integer');
        }

        if ($length < 1) {
            throw new \InvalidArgumentException('Bytes length must be greater than zero');
        }

        if ($length > PHP_INT_MAX) {
            throw new \InvalidArgumentException('Bytes length is too large');
        }

        $unix = ('/' === DIRECTORY_SEPARATOR);
        $windows = ('\\' === DIRECTORY_SEPARATOR);

        $bytes = false;

        // Gunakan openssl.
        $bytes = openssl_random_pseudo_bytes($length, $strong);

        if (false !== $strong && false !== $bytes) {
            if ($length === mb_strlen($bytes, '8bit')) {
                return $bytes;
            }
        }

        // Openssl gagal, coba /dev/urandom (unix)
        if ($unix) {
            $urandom = true;
            $basedir = ini_get('open_basedir');

            if (! empty($basedir)) {
                $paths = explode(PATH_SEPARATOR, strtolower($basedir));
                $urandom = ([] !== array_intersect(['/dev', '/dev/', '/dev/urandom'], $paths));
                unset($paths);
            }

            if ($urandom && @is_readable('/dev/urandom')) {
                $file = fopen('/dev/urandom', 'r');
                $read = 0;
                $local = '';

                while ($read < $length) {
                    $local .= fread($file, $length - $read);
                    $read = mb_strlen($local, '8bit');
                }

                fclose($file);

                $bytes = str_pad($bytes, $length, "\0") ^ str_pad($local, $length, "\0");
            }

            if ($read >= $length && $length === mb_strlen($bytes, '8bit')) {
                return $bytes;
            }
        }

        // /dev/urandom juga gagal, coba mcrypt
        if ($unix && ($windows || (PHP_VERSION_ID <= 50609 || PHP_VERSION_ID >= 50613))
        && extension_loaded('mcrypt')) {
            $bytes = @mcrypt_create_iv($length, (int) MCRYPT_DEV_URANDOM);

            if (false !== $bytes && $length === mb_strlen($bytes, '8bit')) {
                return $bytes;
            }
        }

        // Mcrypt juga masih saja gagal, coba CAPICOM (windows)
        if ($windows && class_exists('COM', false)) {
            try {
                $com = new \COM('CAPICOM.Utilities.1');
                $count = 0;

                do {
                    $bytes .= base64_decode((string) $com->GetRandom($length, 0));

                    if (mb_strlen($bytes, '8bit') >= $length) {
                        $bytes = (string) mb_substr($bytes, 0, $length, '8bit');
                    }

                    ++$count;
                } while ($count < $length);
            } catch (\Throwable $e) {
                // Skip error.
            } catch (\Exception $e) {
                // Skip error.
            }

            if ($bytes && is_string($bytes) && $length === mb_strlen($bytes, '8bit')) {
                return $bytes;
            }
        }

        // Tidak ada lagi yang bisa digunakan. Menyerah.
        throw new \Exception('There is no suitable CSPRNG installed on your system');
    }

    /**
     * Generate cryptographically secure random integer.
     *
     * @param int $min
     * @param int $max
     *
     * @return int
     */
    public static function integers($min, $max)
    {
        $min = (int) $min;
        $min = ($min < ~PHP_INT_MAX) ? ~PHP_INT_MAX : $min;
        $min = ($min > PHP_INT_MAX) ? PHP_INT_MAX : $min;

        $max = (int) $max;
        $max = ($max < ~PHP_INT_MAX) ? ~PHP_INT_MAX : $max;
        $max = ($max > PHP_INT_MAX) ? PHP_INT_MAX : $max;

        if ($min > $max) {
            throw new \Exception('Minimum value must be less than or equal to the maximum value');
        }

        if ($max === $min) {
            return (int) $min;
        }

        $attempts = 0;
        $bits = 0;
        $bytes = 0;
        $mask = 0;
        $shift = 0;
        $range = $max - $min;

        if (! is_int($range)) {
            $bytes = PHP_INT_SIZE;
            $mask = ~0;
        } else {
            while ($range > 0) {
                if (0 === $bits % 8) {
                    ++$bytes;
                }

                ++$bits;
                $range >>= 1;
                $mask = $mask << 1 | 1;
            }

            $shift = $min;
        }

        $val = 0;

        do {
            if ($attempts > 128) {
                throw new \Exception('RNG is broken - too many rejections');
            }

            $random = static::bytes($bytes);
            $val &= 0;

            for ($i = 0; $i < $bytes; ++$i) {
                $val |= ord($random[$i]) << ($i * 8);
            }

            $val &= $mask;
            $val += $shift;
            ++$attempts;
        } while (! is_int($val) || $val > $max || $val < $min);

        return (int) $val;
    }

    /**
     * Buat string UUID (versi 4).
     *
     * @return string
     */
    public static function uuid()
    {
        $uuid = bin2hex(static::bytes(16));

        return sprintf(
            '%08s-%04s-4%03s-%04x-%012s',
            substr($uuid, 0, 8),
            substr($uuid, 8, 4),
            substr($uuid, 13, 3),
            hexdec(substr($uuid, 16, 4)) & 0x3fff | 0x8000,
            substr($uuid, 20, 12)
        );
    }

    /**
     * Cek apakah string cocok dengan pola yang diberikan.
     *
     * @param string|array $pattern
     * @param string       $value
     *
     * @return bool
     */
    public static function is($pattern, $value)
    {
        $patterns = Arr::wrap($pattern);

        if (empty($patterns)) {
            return false;
        }

        foreach ($patterns as $pattern) {
            if ($pattern === $value) {
                return true;
            }

            $pattern = str_replace('\*', '.*', preg_quote($pattern, '#'));

            if (1 === preg_match('#^'.$pattern.'\z#u', $value)) {
                return true;
            }
        }

        return false;
    }

    public static function replace_first($search, $replace, $subject)
    {
        if ('' === $search) {
            return $subject;
        }

        $position = strpos($subject, $search);

        if (false !== $position) {
            return substr_replace($subject, $replace, $position, strlen($search));
        }

        return $subject;
    }

    public static function replace_last($search, $replace, $subject)
    {
        if ($search === '') {
            return $subject;
        }

        $position = strrpos($subject, $search);

        if ($position !== false) {
            return substr_replace($subject, $replace, $position, strlen($search));
        }

        return $subject;
    }

    public static function replace_array($search, array $replace, $subject)
    {
        $segments = explode($search, $subject);
        $result = array_shift($segments);

        foreach ($segments as $segment) {
            $replacer = array_shift($replace);
            $result .= ($replacer ? $replacer : $search).$segment;
        }

        return $result;
    }

    public static function before($subject, $search)
    {
        return ('' === $search) ? $subject : explode($search, $subject)[0];
    }

    public static function after($subject, $search)
    {
        return ('' === $search) ? $subject : array_reverse(explode($search, $subject, 2))[0];
    }

    public static function camel($value)
    {
        if (isset(static::$camel[$value])) {
            return static::$camel[$value];
        }

        static::$camel[$value] = lcfirst(static::studly($value));

        return static::$camel[$value];
    }

    public static function studly($value)
    {
        $key = $value;

        if (isset(static::$studly[$key])) {
            return static::$studly[$key];
        }

        $value = ucwords(str_replace(['-', '_'], ' ', $value));
        static::$studly[$key] = str_replace(' ', '', $value);

        return static::$studly[$key];
    }

    public static function kebab($value)
    {
        return static::snake($value, '-');
    }

    public static function snake($value, $delimiter = '_')
    {
        $key = $value;

        if (isset(static::$snake[$key][$delimiter])) {
            return static::$snake[$key][$delimiter];
        }

        $chars = static::characterify($value);
        $is_lower = is_string($chars) && '' !== $chars && ! preg_match('/[^a-z]/', $chars);

        if (! $is_lower) {
            $value = preg_replace('/\s+/u', '', ucwords($value));
            $value = static::lower(preg_replace('/(.)(?=[A-Z])/u', '$1'.$delimiter, $value));
        }

        static::$snake[$key][$delimiter] = $value;

        return static::$snake[$key][$delimiter];
    }

    public static function contains($haystack, $needles)
    {
        $needles = (array) $needles;

        foreach ($needles as $needle) {
            if ('' !== $needle && false !== mb_strpos($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    public static function contains_all($haystack, array $needles)
    {
        foreach ($needles as $needle) {
            if (! static::contains($haystack, $needle)) {
                return false;
            }
        }

        return true;
    }

    public static function start($value, $prefix)
    {
        return $prefix.preg_replace('/^(?:'.preg_quote($prefix, '/').')+/u', '', $value);
    }

    public static function starts_with($haystack, $needle)
    {
        return ('' !== (string) $needle && 0 === strncmp($haystack, $needle, strlen($needle)));
    }

    public static function ends_with($haystack, $needle)
    {
        return ('' !== $needle && ((string) $needle === substr($haystack, -strlen($needle))));
    }

    public static function finish($value, $cap)
    {
        return preg_replace('/(?:'.preg_quote($cap, '/').')+$/u', '', $value).$cap;
    }

    public static function parse_callback($callback, $default = null)
    {
        return static::contains($callback, '@') ? explode('@', $callback, 2) : [$callback, $default];
    }

    /**
     * Konversikan integer ke char mengikuti aturan ctype.
     *
     * @param string|int $value
     *
     * @return mixed
     */
    public static function characterify($value)
    {
        if (! is_int($value)) {
            return $value;
        }

        if ($value < -128 || $value > 255) {
            return (string) $value;
        }

        if ($value < 0) {
            $value += 256;
        }

        return chr($value);
    }
}
