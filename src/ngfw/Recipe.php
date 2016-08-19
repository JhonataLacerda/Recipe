<?php

namespace ngfw;

class Recipe
{
    /**
     * Get a Website favicon image.
     *
     * @param string $url        website url
     * @param array  $attributes Optional, additional key/value attributes to include in the IMG tag
     *
     * @return string containing complete image tag
     */
    public static function getFavicon($url, $attributes = [])
    {
        if (self::ishttps()) {
            $protocol = 'https://';
        } else {
            $protocol = 'http://';
        }
        $apiUrl = $protocol.'www.google.com/s2/favicons?domain=';
        $attr = self::arrayToString($attributes);

        if (strpos($url, 'http') !== false) {
            $url = str_replace('http://', '', $url);
        }

        return '<img src="'.$apiUrl.$url.'" '.trim($attr).' />';
    }

    /**
     * Get a QR code.
     *
     * @param string $string     String to generate QR code for.
     * @param int    $width      QR code width
     * @param int    $height     QR code height
     * @param array  $attributes Optional, additional key/value attributes to include in the IMG tag
     *
     * @return string containing complete image tag
     */
    public static function getQRcode($string, $width = 150, $height = 150, $attributes = [])
    {
        if (self::ishttps()) {
            $protocol = 'https://';
        } else {
            $protocol = 'http://';
        }
        $attr = self::arrayToString($attributes);
        $apiUrl = $protocol.'chart.apis.google.com/chart?chs='.$width.'x'.$height.'&cht=qr&chl='.urlencode($string);

        return '<img src="'.$apiUrl.'" '.trim($attr).' />';
    }

    /**
     * Get file extension.
     *
     * @param string $filename File path
     *
     * @return string file extension
     */
    public static function getFileExtension($filename)
    {
        return pathinfo($filename, PATHINFO_EXTENSION);
    }

    /**
     * Get a Gravatar for email.
     *
     * @param string $email      The email address
     * @param int    $size       Size in pixels, defaults to 80 (in px), available values from 1 to 2048
     * @param string $default    Default imageset to use, available values: 404, mm, identicon, monsterid, wavatar
     * @param string $rating     Maximum rating (inclusive), available values:  g, pg, r, x
     * @param array  $attributes Optional, additional key/value attributes to include in the IMG tag
     *
     * @return string containing complete image tag
     */
    public static function getGravatar($email, $size = 80, $default = 'mm', $rating = 'g', $attributes = [])
    {
        $attr = self::arrayToString($attributes);
        if (self::ishttps()) {
            $url = 'https://secure.gravatar.com/';
        } else {
            $url = 'http://www.gravatar.com/';
        }

        return '<img src="'.$url.'avatar.php?gravatar_id='.md5(strtolower(trim($email))).'&default='.$default.'&size='.$size.'&rating='.$rating.'" width="'.$size.'px" height="'.$size.'px" '.trim($attr).' />';
    }

    /**
     * Create HTML A Tag.
     *
     * @param string $link       URL or Email address
     * @param string $text       Optional, If link text is empty, $link variable value will be used by default
     * @param array  $attributes Optional, additional key/value attributes to include in the IMG tag
     *
     * @return string containing complete a tag
     */
    public static function createLinkTag($link, $text = '', $attributes = [])
    {
        if (self::validateEmail($link)) {
            $linkTag = '<a href="mailto:'.$link.'"';
        } else {
            $linkTag = '<a href="'.$link.'"';
        }
        $attr = '';
        if (!isset($attributes['title']) && !empty($text)) {
            $linkTag .= ' title="'.str_replace('"', '', strip_tags($text)).'" ';
        }
        if (empty($text)) {
            $text = $link;
        }
        $attr .= self::arrayToString($attributes);
        $linkTag .= trim($attr).'>'.htmlspecialchars($text, ENT_QUOTES, 'UTF-8').'</a>';

        return $linkTag;
    }

    /**
     * Validate Email address.
     *
     * @param string $address Email address to validate
     *
     * @return bool True if email address is valid, false is returned otherwise
     */
    public static function validateEmail($address)
    {
        if (filter_var($address, FILTER_VALIDATE_EMAIL)) {
            list(, $mailDomain) = explode('@', $address);
            if (checkdnsrr($mailDomain, 'MX')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate URL.
     *
     * @param string $url Website URL
     *
     * @return bool True if URL is valid, false is returned otherwise
     */
    public static function validateURL($url)
    {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return true;
        }

        return false;
    }

    /**
     * Read RSS feed as array
     * requires.
     *
     * @see http://php.net/manual/en/simplexml.installation.php
     *
     * @param string $url RSS feed URL
     *
     * @return array Representation of XML feed
     */
    public static function rssReader($url)
    {
        if (strpos($url, 'http') === false) {
            $url = 'http://'.$url;
        }
        $feed = self::curl($url);
        $xml = simplexml_load_string($feed, 'SimpleXMLElement', LIBXML_NOCDATA);

        return self::objectToArray($xml);
    }

    /**
     * Convert objecte to the array.
     *
     * @param object $object PHP object
     *
     * @return array
     */
    public static function objectToArray($object)
    {
        if (!is_object($object) && !is_array($object)) {
            return $object;
        }
        if (is_object($object)) {
            $object = get_object_vars($object);
        }

        return array_map(['self', 'objectToArray'], $object);
    }

    /**
     * Convert array to the object.
     *
     * @param array $array PHP array
     *
     * @return object
     */
    public static function arrayToObject($array)
    {
        if (!is_array($array)) {
            return $array;
        }

        $object = new \stdClass();
        if (is_array($array) && count($array) > 0) {
            foreach ($array as $name => $value) {
                $object->$name = self::arrayToObject($value);
            }

            return $object;
        } else {
            return false;
        }
    }

    /**
     * Convert Array to string
     * expected output: <key1>="value1" <key2>="value2".
     *
     * @param array $array array to convert to string
     *
     * @return string
     */
    public static function arrayToString($array = [])
    {
        $string = '';
        if (isset($array) && is_array($array) && !empty($array)) {
            foreach ($array as $key => $value) {
                $string .= $key.'="'.$value.'" ';
            }
        }

        return rtrim($string, ' ');
    }

    /**
     * Takes HEX color code value and converts to a RGB value.
     *
     * @param string $color Color hex value, example: #000000, #000 or 000000, 000
     *
     * @return string color rbd value
     */
    public static function hex2rgb($color)
    {
        $color = str_replace('#', '', $color);
        if (strlen($color) == 3):
            list($r, $g, $b) = [$color[0].$color[0], $color[1].$color[1], $color[2].$color[2]]; else:
            list($r, $g, $b) = [$color[0].$color[1], $color[2].$color[3], $color[4].$color[5]];
        endif;
        $r = hexdec($r);
        $g = hexdec($g);
        $b = hexdec($b);

        return 'rgb('.$r.', '.$g.', '.$b.')';
    }

    /**
     * Takes RGB color value and converts to a HEX color code
     * Could be used as Recipe::rgb2hex("rgb(0,0,0)") or Recipe::rgb2hex(0,0,0).
     *
     * @param mixed $r Full rgb,rgba string or red color segment
     * @param mixed $g null or green color segment
     * @param mixed $b null or blue color segment
     *
     * @return string hex color value
     */
    public static function rgb2hex($r, $g = null, $b = null)
    {
        if (strpos($r, 'rgb') !== false || strpos($r, 'rgba') !== false) {
            preg_match_all('/\(([^\)]*)\)/', $r, $matches);
            if (isset($matches[1][0])) {
                list($r, $g, $b) = explode(',', $matches[1][0]);
            } else {
                return false;
            }
        }

        $result = '';
        foreach ([$r, $g, $b] as $c) {
            $hex = base_convert($c, 10, 16);
            $result .= ($c < 16) ? ('0'.$hex) : $hex;
        }

        return '#'.$result;
    }

    /**
     * Generate Random Password.
     *
     * @param int $length length of generated password, default 8
     *
     * @return string Generated Password
     */
    public static function generateRandomPassword($length = 8)
    {
        $alphabet = 'abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789';
        $pass = [];
        $alphaLength = strlen($alphabet) - 1;
        for ($i = 0; $i < $length; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }

        return implode($pass);
    }

    /**
     * Simple Encode string.
     *
     * @param string $string  String you would like to encode
     * @param string $passkey salt for encoding
     *
     * @return string
     */
    public static function simpleEncode($string, $passkey = null)
    {
        if (!isset($passkey) || empty($passkey)) {
            $key = self::generateServerSpecificHash();
        } else {
            $key = $passkey;
        }
        $result = '';
        for ($i = 0; $i < strlen($string); $i++) {
            $char = substr($string, $i, 1);
            $keychar = substr($key, ($i % strlen($key)) - 1, 1);
            $char = chr(ord($char) + ord($keychar));
            $result .= $char;
        }

        return base64_encode($result);
    }

    /**
     * Simple Decode string.
     *
     * @param string $string  String encoded via Recipe::simpleEncode()
     * @param string $passkey salt for encoding
     *
     * @return string
     */
    public static function simpleDecode($string, $passkey = null)
    {
        if (!isset($passkey) || empty($passkey)) {
            $key = self::generateServerSpecificHash();
        } else {
            $key = $passkey;
        }
        $result = '';
        $string = base64_decode($string);
        for ($i = 0; $i < strlen($string); $i++) {
            $char = substr($string, $i, 1);
            $keychar = substr($key, ($i % strlen($key)) - 1, 1);
            $char = chr(ord($char) - ord($keychar));
            $result .= $char;
        }

        return $result;
    }

    /**
     * Generate Server Specific hash.
     *
     * @method generateServerSpecificHash
     *
     * @return string
     */
    public static function generateServerSpecificHash()
    {
        return (isset($_SERVER['SERVER_NAME']) && !empty($_SERVER['SERVER_NAME'])) ? md5($_SERVER['SERVER_NAME']) : md5(pathinfo(__FILE__, PATHINFO_FILENAME));
    }

    /**
     * Check to see if the current page is being server over SSL or not.
     *
     * @return bool
     */
    public static function isHttps()
    {
        if (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        return false;
    }

    /**
     * Determine if current page request type is ajax.
     *
     * @return bool
     */
    public static function isAjax()
    {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            return true;
        }

        return false;
    }

    /**
     * Check if number is odd.
     *
     * @param int $num integer to check
     *
     * @return bool
     */
    public static function isNumberOdd($num)
    {
        return $num % 2 !== 0;
    }

    /**
     * Check if number is even.
     *
     * @param int $num integer to check
     *
     * @return bool
     */
    public static function isNumberEven($num)
    {
        return $num % 2 == 0;
    }

    /**
     * Return the current URL.
     *
     * @return string
     */
    public static function getCurrentURL()
    {
        $url = '';
        if (self::isHttps()) {
            $url .= 'https://';
        } else {
            $url .= 'http://';
        }
        if (isset($_SERVER['PHP_AUTH_USER'])) {
            $url .= $_SERVER['PHP_AUTH_USER'];
            if (isset($_SERVER['PHP_AUTH_PW'])) {
                $url .= ':'.$_SERVER['PHP_AUTH_PW'];
            }
            $url .= '@';
        }
        if (isset($_SERVER['HTTP_HOST'])) {
            $url .= $_SERVER['HTTP_HOST'];
        }
        if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != 80) {
            $url .= ':'.$_SERVER['SERVER_PORT'];
        }
        if (!isset($_SERVER['REQUEST_URI'])) {
            $url .= substr($_SERVER['PHP_SELF'], 1);
            if (isset($_SERVER['QUERY_STRING'])):
                $url .= '?'.$_SERVER['QUERY_STRING'];
            endif;
        } else {
            $url .= $_SERVER['REQUEST_URI'];
        }

        return $url;
    }

    /**
     * Returns the IP address of the client.
     *
     * @param bool $headerContainingIPAddress Default false
     *
     * @return string
     */
    public static function getClientIP($headerContainingIPAddress = null)
    {
        if (!empty($headerContainingIPAddress)) {
            return isset($_SERVER[$headerContainingIPAddress]) ? trim($_SERVER[$headerContainingIPAddress]) : false;
        }
        $knowIPkeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        foreach ($knowIPkeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Detect if user is on mobile device.
     *
     * @return bool
     */
    public static function isMobile()
    {
        $useragent = $_SERVER['HTTP_USER_AGENT'];
        if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $useragent) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($useragent, 0, 4))) {
            return true;
        }

        return false;
    }

    /**
     * Get user browser.
     *
     * @return string
     */
    public static function getBrowser()
    {
        $u_agent = $_SERVER['HTTP_USER_AGENT'];
        $browserName = $ub = $platform = 'Unknown';
        if (preg_match('/linux/i', $u_agent)) {
            $platform = 'Linux';
        } elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
            $platform = 'Mac OS';
        } elseif (preg_match('/windows|win32/i', $u_agent)) {
            $platform = 'Windows';
        }

        if (preg_match('/MSIE/i', $u_agent) && !preg_match('/Opera/i', $u_agent)) {
            $browserName = 'Internet Explorer';
            $ub = 'MSIE';
        } elseif (preg_match('/Firefox/i', $u_agent)) {
            $browserName = 'Mozilla Firefox';
            $ub = 'Firefox';
        } elseif (preg_match('/Chrome/i', $u_agent)) {
            $browserName = 'Google Chrome';
            $ub = 'Chrome';
        } elseif (preg_match('/Safari/i', $u_agent)) {
            $browserName = 'Apple Safari';
            $ub = 'Safari';
        } elseif (preg_match('/Opera/i', $u_agent)) {
            $browserName = 'Opera';
            $ub = 'Opera';
        } elseif (preg_match('/Netscape/i', $u_agent)) {
            $browserName = 'Netscape';
            $ub = 'Netscape';
        }

        $known = ['Version', $ub, 'other'];
        $pattern = '#(?<browser>'.implode('|', $known).')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
        preg_match_all($pattern, $u_agent, $matches);
        $i = count($matches['browser']);
        if ($i != 1) {
            if (strripos($u_agent, 'Version') < strripos($u_agent, $ub)) {
                $version = $matches['version'][0];
            } else {
                $version = $matches['version'][1];
            }
        } else {
            $version = $matches['version'][0];
        }
        if ($version == null || $version == '') {
            $version = '?';
        }

        return implode(', ', [$browserName, 'Version: '.$version, $platform]);
    }

    /**
     * Get client location.
     *
     * @return string|false
     */
    public static function getClientLocation()
    {
        $result = false;
        $ip_data = @json_decode(self::curl('http://www.geoplugin.net/json.gp?ip='.self::getClientIP()));
        if (isset($ip_data) && $ip_data->geoplugin_countryName != null) {
            $result = $ip_data->geoplugin_city.', '.$ip_data->geoplugin_countryCode;
        }

        return $result;
    }

    /**
     * Convert number to word representation.
     *
     * @param int $number number to convert to word
     *
     * @return string converted string
     */
    public static function numberToWord($number)
    {
        $hyphen = '-';
        $conjunction = ' and ';
        $separator = ', ';
        $negative = 'negative ';
        $decimal = ' point ';
        $fraction = null;
        $dictionary = [0 => 'zero', 1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five', 6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine', 10 => 'ten', 11 => 'eleven', 12 => 'twelve', 13 => 'thirteen', 14 => 'fourteen', 15 => 'fifteen', 16 => 'sixteen', 17 => 'seventeen', 18 => 'eighteen', 19 => 'nineteen', 20 => 'twenty', 30 => 'thirty', 40 => 'fourty', 50 => 'fifty', 60 => 'sixty', 70 => 'seventy', 80 => 'eighty', 90 => 'ninety', 100 => 'hundred', 1000 => 'thousand', 1000000 => 'million', 1000000000 => 'billion', 1000000000000 => 'trillion', 1000000000000000 => 'quadrillion', 1000000000000000000 => 'quintillion'];

        if (!is_numeric($number)) {
            return false;
        }

        if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
            trigger_error('numberToWord only accepts numbers between -'.PHP_INT_MAX.' and '.PHP_INT_MAX, E_USER_WARNING);

            return false;
        }

        if ($number < 0) {
            return $negative.self::numberToWord(abs($number));
        }

        if (strpos($number, '.') !== false) {
            list($number, $fraction) = explode('.', $number);
        }

        switch (true) {
            case $number < 21:
                $string = $dictionary[$number];
                break;

            case $number < 100:
                $tens = ((int) ($number / 10)) * 10;
                $units = $number % 10;
                $string = $dictionary[$tens];
                if ($units) {
                    $string .= $hyphen.$dictionary[$units];
                }
                break;

            case $number < 1000:
                $hundreds = $number / 100;
                $remainder = $number % 100;
                $string = $dictionary[$hundreds].' '.$dictionary[100];
                if ($remainder) {
                    $string .= $conjunction.self::numberToWord($remainder);
                }
                break;

            default:
                $baseUnit = pow(1000, floor(log($number, 1000)));
                $numBaseUnits = (int) ($number / $baseUnit);
                $remainder = $number % $baseUnit;
                $string = self::numberToWord($numBaseUnits).' '.$dictionary[$baseUnit];
                if ($remainder) {
                    $string .= $remainder < 100 ? $conjunction : $separator;
                    $string .= self::numberToWord($remainder);
                }
                break;
        }

        if (null !== $fraction && is_numeric($fraction)) {
            $string .= $decimal;
            $words = [];
            foreach (str_split((string) $fraction) as $number) {
                $words[] = $dictionary[$number];
            }
            $string .= implode(' ', $words);
        }

        return $string;
    }

    /**
     * Convert seconds to real time.
     *
     * @param int  $seconds       time in seconds
     * @param bool $returnAsWords return time in words (example one minute and 20 seconds) if value is True or (1 minute and 20 seconds) if value is false, default false
     *
     * @return string
     */
    public static function secondsToText($seconds, $returnAsWords = false)
    {
        $periods = ['year' => 3.156e+7, 'month' => 2.63e+6, 'week' => 604800, 'day' => 86400, 'hour' => 3600, 'minute' => 60, 'second' => 1];
        $parts = [];
        foreach ($periods as $name => $dur) {
            $div = floor($seconds / $dur);
            if ($div == 0) {
                continue;
            } elseif ($div == 1) {
                $parts[] = ($returnAsWords ? self::numberToWord($div) : $div).' '.$name;
            } else {
                $parts[] = ($returnAsWords ? self::numberToWord($div) : $div).' '.$name.'s';
            }
            $seconds %= $dur;
        }
        $last = array_pop($parts);
        if (empty($parts)):
            return $last; else:
            return implode(', ', $parts).' and '.$last;
        endif;
    }

    /**
     * Convert minutes to real time.
     *
     * @param int  $minutes       time in minutes
     * @param bool $returnAsWords return time in words (example one hour and 20 minutes) if value is True or (1 hour and 20 minutes) if value is false, default false
     *
     * @return string
     */
    public static function minutesToText($minutes, $returnAsWords = false)
    {
        $seconds = $minutes * 60;

        return self::secondsToText($seconds, $returnAsWords);
    }

    /**
     * Convert hours to real time.
     *
     * @param int  $hours         time in hours
     * @param bool $returnAsWords return time in words (example one hour) if value is True or (1 hour) if value is false, default false
     *
     * @return string
     */
    public static function hoursToText($hours, $returnAsWords = false)
    {
        $seconds = $hours * 3600;

        return self::secondsToText($seconds, $returnAsWords);
    }

    /**
     * Truncate String with or without ellipsis.
     *
     * @param string $string      String to truncate
     * @param int    $maxLength   Maximum length of string
     * @param bool   $addEllipsis if True, "..." is added in the end of the string, default true
     * @param bool   $wordsafe    if True, Words will not be cut in the middle
     *
     * @return string Shotened Text
     */
    public static function shortenString($string, $maxLength, $addEllipsis = true, $wordsafe = false)
    {
        $ellipsis = '';
        $maxLength = max($maxLength, 0);
        if (mb_strlen($string) <= $maxLength) {
            return $string;
        }
        if ($addEllipsis) {
            $ellipsis = mb_substr('...', 0, $maxLength);
            $maxLength -= mb_strlen($ellipsis);
            $maxLength = max($maxLength, 0);
        }
        if ($wordsafe) {
            $string = preg_replace('/\s+?(\S+)?$/', '', mb_substr($string, 0, $maxLength));
        } else {
            $string = mb_substr($string, 0, $maxLength);
        }
        if ($addEllipsis) {
            $string .= $ellipsis;
        }

        return $string;
    }

    /**
     * Make Curl call.
     *
     * @param string $url        URL to curl
     * @param string $method     GET or POST, Default GET
     * @param mixed  $data       Data to post, Default false
     * @param mixed  $headers    Additional headers, example: array ("Accept: application/json")
     * @param bool   $returnInfo Whether or not to retrieve curl_getinfo()
     *
     * @return string|array if $returnInfo is set to True, array is returned with two keys, contents (will contain response) and info (information regarding a specific transfer), otherwise response content is returned
     */
    public static function curl($url, $method = 'GET', $data = false, $headers = false, $returnInfo = false)
    {
        $ch = curl_init();
        $info = null;
        if (strtoupper($method) == 'POST') {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data !== false) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        } else {
            if ($data !== false) {
                if (is_array($data)) {
                    $dataTokens = [];
                    foreach ($data as $key => $value) {
                        array_push($dataTokens, urlencode($key).'='.urlencode($value));
                    }
                    $data = implode('&', $dataTokens);
                }
                curl_setopt($ch, CURLOPT_URL, $url.'?'.$data);
            } else {
                curl_setopt($ch, CURLOPT_URL, $url);
            }
        }
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        if ($headers !== false) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $contents = curl_exec($ch);
        if ($returnInfo) {
            $info = curl_getinfo($ch);
        }
        curl_close($ch);
        if ($returnInfo) {
            return ['contents' => $contents, 'info' => $info];
        } else {
            return $contents;
        }
    }

    /**
     * Get information on a short URL. Find out where it goes.
     *
     * @param string $shortURL shortened URL
     *
     * @return mixed full url or false
     */
    public static function expandShortUrl($shortURL)
    {
        if (!empty($shortURL)) {
            $headers = get_headers($shortURL, 1);
            if (isset($headers['Location'])) {
                return $headers['Location'];
            } else {
                $data = self::curl($shortURL);
                preg_match_all('/<[\s]*meta[\s]*http-equiv="?'.'([^>"]*)"?[\s]*'.'content="?([^>"]*)"?[\s]*[\/]?[\s]*>/si', $data, $match);
                if (isset($match) && is_array($match) && count($match) == 3) {
                    $originals = $match[0];
                    $names = $match[1];
                    $values = $match[2];
                    if ((isset($originals) && isset($names) && isset($values)) && count($originals) == count($names) && count($names) == count($values)) {
                        $metaTags = [];
                        for ($i = 0, $limit = count($names); $i < $limit; $i++) {
                            $metaTags[$names[$i]] = ['html' => htmlentities($originals[$i]), 'value' => $values[$i]];
                        }
                    }
                }
                if (isset($metaTags['refresh']['value']) && !empty($metaTags['refresh']['value'])) {
                    $returnData = explode('=', $metaTags['refresh']['value']);
                    if (isset($returnData[1]) && !empty($returnData[1])) {
                        return $returnData[1];
                    }
                }
            }
        }

        return false;
    }

    /**
     * Get Alexa ranking for domain name.
     *
     * @param string $domain Domain name to get ranking for
     *
     * @return mixed false if ranking is found, otherwise integer
     */
    public static function getAlexaRank($domain)
    {
        $domain = preg_replace('~^https?://~', '', $domain);
        $alexa = 'http://data.alexa.com/data?cli=10&dat=s&url=%s';
        $request_url = sprintf($alexa, urlencode($domain));
        $xml = simplexml_load_file($request_url);
        if (!isset($xml->SD[1])) {
            return false;
        }
        $nodeAttributes = $xml->SD[1]->POPULARITY->attributes();
        $text = (int) $nodeAttributes['TEXT'];

        return $text;
    }

    /**
     * Get Google page rank for url.
     *
     * @param string $url URL to get Google Page rank for
     *
     * @return mixed integer or false
     */
    public static function getGooglePageRank($url)
    {

        // based on code by Mohammed Hijazi
        function StrToNum($Str, $Check, $Magic)
        {
            $Int32Unit = 4294967296;

            // 2^32
            $length = strlen($Str);
            for ($i = 0; $i < $length; $i++) {
                $Check *= $Magic;
                if ($Check >= $Int32Unit) {
                    $Check = ($Check - $Int32Unit * (int) ($Check / $Int32Unit));
                    $Check = ($Check < -2147483648) ? ($Check + $Int32Unit) : $Check;
                }
                $Check += ord($Str[$i]);
            }

            return $Check;
        }

        function HashURL($String)
        {
            $Check1 = StrToNum($String, 0x1505, 0x21);
            $Check2 = StrToNum($String, 0, 0x1003F);
            $Check1 >>= 2;
            $Check1 = (($Check1 >> 4) & 0x3FFFFC0) | ($Check1 & 0x3F);
            $Check1 = (($Check1 >> 4) & 0x3FFC00) | ($Check1 & 0x3FF);
            $Check1 = (($Check1 >> 4) & 0x3C000) | ($Check1 & 0x3FFF);
            $T1 = (((($Check1 & 0x3C0) << 4) | ($Check1 & 0x3C)) << 2) | ($Check2 & 0xF0F);
            $T2 = (((($Check1 & 0xFFFFC000) << 4) | ($Check1 & 0x3C00)) << 0xA) | ($Check2 & 0xF0F0000);

            return $T1 | $T2;
        }

        function CheckHash($Hashnum)
        {
            $CheckByte = 0;
            $Flag = 0;
            $HashStr = sprintf('%u', $Hashnum);
            $length = strlen($HashStr);
            for ($i = $length - 1; $i >= 0; $i--) {
                $Re = $HashStr[$i];
                if (1 === ($Flag % 2)) {
                    $Re += $Re;
                    $Re = (int) ($Re / 10) + ($Re % 10);
                }
                $CheckByte += $Re;
                $Flag++;
            }
            $CheckByte %= 10;
            if (0 !== $CheckByte) {
                $CheckByte = 10 - $CheckByte;
                if (1 === ($Flag % 2)) {
                    if (1 === ($CheckByte % 2)) {
                        $CheckByte += 9;
                    }
                    $CheckByte >>= 1;
                }
            }

            return '7'.$CheckByte.$HashStr;
        }

        $query = 'http://toolbarqueries.google.com/tbr?client=navclient-auto&ch='.CheckHash(HashURL($url)).'&features=Rank&q=info:'.$url.'&num=100&filter=0';

        $data = file_get_contents($query);
        $pos = strpos($data, 'Rank_');

        if ($pos === false) {
            return false;
        } else {
            $pagerank = substr($data, $pos + 9);

            return (int) $pagerank;
        }
    }

    /**
     * Shorten URL via tinyurl.com service.
     *
     * @param string $url URL to shorten
     *
     * @return mixed shortend url or false
     */
    public static function getTinyUrl($url)
    {
        if (strpos($url, 'http') === false) {
            $url = 'http://'.$url;
        }
        $gettiny = self::curl('http://tinyurl.com/api-create.php?url='.$url);
        if (isset($gettiny) && !empty($gettiny)) {
            return $gettiny;
        }

        return false;
    }

    /**
     * Get keyword suggestion from Google.
     *
     * @param string $keyword keyword to get suggestions for
     *
     * @return mixed array of keywords or false
     */
    public static function getKeywordSuggestionsFromGoogle($keyword)
    {
        $data = self::curl('http://suggestqueries.google.com/complete/search?output=firefox&client=firefox&hl=en-US&q='.urlencode($keyword));
        if (($data = json_decode($data, true)) !== null) {
            if (!empty($data[1])) {
                return $data[1];
            }
        }

        return false;
    }

    /**
     * Search wikipedia.
     *
     * @param string $keyword Keywords to search in wikipedia
     *
     * @return mixed Array or false
     */
    public static function wikiSearch($keyword)
    {
        $apiurl = 'http://wikipedia.org/w/api.php?action=opensearch&search='.urlencode($keyword).'&format=xml&limit=1';
        $data = self::curl($apiurl);
        $xml = simplexml_load_string($data);
        if ((string) $xml->Section->Item->Description) {
            $array = [];
            $array['title'] = (string) $xml->Section->Item->Text;
            $array['description'] = (string) $xml->Section->Item->Description;
            $array['url'] = (string) $xml->Section->Item->Url;
            if (isset($xml->Section->Item->Image)) {
                $img = (string) $xml->Section->Item->Image->attributes()->source;
                $array['image'] = str_replace('/50px-', '/200px-', $img);
            }

            return $array;
        }

        return false;
    }

    /**
     * Build notification message.
     *
     * @param string $notification Text to display in notification
     * @param string $type         Notification type, available notifications: success, warning, error and info
     * @param array  $attributes   Optional, additional key/value attributes to include in the DIV tag
     *
     * @return string containing complete div tag
     */
    public static function notification($notification, $type = null, $attributes = [])
    {
        $attr = self::arrayToString($attributes);
        if (isset($notification) && !empty($notification)) {
            switch (strtolower($type)) {

                case 'success':
                    $css = 'border-color: #bdf2a6;color: #2a760a;background-color: #eefde7;';
                    break;

                case 'warning':
                    $css = 'border-color: #f2e5a6;color: #76640a;background-color: #fdf9e7;';
                    break;

                case 'error':
                    $css = 'border-color: #f2a6a6;color: #760a0a;background-color: #fde7e7;';
                    break;

                case 'info':
                default:
                    $css = 'border-color: #a6d9f2;color: #0a5276;background-color: #e7f6fd;';
                    break;
            }

            return '<div style="display: block;padding: 0.5em;border: solid 1px;border-radius: 0.125em;margin-bottom: 1em; '.$css.'" '.$attr.' role="alert">'.$notification.'</div>';
        }

        return false;
    }

    /**
     * Parse text to find url's for embed enabled services like: youtube.com, blip.tv, vimeo.com, dailymotion.com, flickr.com, smugmug.com, hulu.com, revision3.com, wordpress.tv, funnyordie.com, soundcloud.com, slideshare.net and instagram.com and embed elements automatically.
     *
     * @param string $string text to parse
     * @param string $width  max width of embeded element
     * @param string $height max heigth of embeded element
     *
     * @return string
     */
    public static function autoEmbed($string, $width = '560', $height = '315')
    {
        $providers = ['~https?://(?:[0-9A-Z-]+\.)?(?:youtu\.be/|youtube(?:-nocookie)?\.com\S*[^\w\s-])([\w-]{11})(?=[^\w-]|$)[?=&+%\w.-]*~ix' => 'http://www.youtube.com/oembed', '#https?://blip\.tv/(.+)#i' => 'http://blip.tv/oembed/', '~https?://(?:[0-9A-Z-]+\.)?(?:vimeo.com\S*[^\w\s-])([\w-]{1,20})(?=[^\w-]|$)[?=&+%\w.-]*~ix' => 'http://vimeo.com/api/oembed.{format}', '#https?://(www\.)?dailymotion\.com/.*#i' => 'http://www.dailymotion.com/services/oembed', '#https?://(www\.)?flickr\.com/.*#i' => 'http://www.flickr.com/services/oembed/', '#https?://(.+\.)?smugmug\.com/.*#i' => 'http://api.smugmug.com/services/oembed/', '#https?://(www\.)?hulu\.com/watch/.*#i' => 'http://www.hulu.com/api/oembed.{format}', '#https?://revision3\.com/(.+)#i' => 'http://revision3.com/api/oembed/', '#https?://wordpress\.tv/(.+)#i' => 'http://wordpress.tv/oembed/', '#https?://(www\.)?funnyordie\.com/videos/.*#i' => 'http://www.funnyordie.com/oembed', '#https?://(www\.)?soundcloud\.com/.*#i' => 'http://soundcloud.com/oembed', '#https?://(www\.)?slideshare.net/*#' => 'http://www.slideshare.net/api/oembed/2', '#http://instagr(\.am|am\.com)/p/.*#i' => 'http://api.instagram.com/oembed'];
        $string = preg_replace_callback('@(^|[^"|^\'])(https?://?([-\w]+\.[-\w\.]+)+\w(:\d+)?(/([-\w/_\.]*(\?\S+)?)?)*)@', function ($matches) use ($providers, $width, $height) {
            $url = trim($matches[0]);
            $url = explode('#', $url);
            $url = reset($url);

            $provider = $requestURL = false;

            foreach ($providers as $pattern => $provider) {
                if (preg_match($pattern, $url)) {
                    if ($provider == 'http://www.youtube.com/oembed') {
                        $url = str_replace('www.youtu.be/', 'www.youtube.com/watch?v=', $url);
                    }
                    $requestURL = str_replace('{format}', 'json', $provider);
                    break;
                }
            }
            if ($requestURL !== false) {
                $params = ['maxwidth' => $width, 'maxheight' => $height, 'format' => 'json'];

                $requestURL = $requestURL.'?url='.$url.'&'.http_build_query($params);
                $data = json_decode(self::curl($requestURL), true);

                switch ($data['type']) {
                    case 'photo':
                        if (empty($data['url']) || empty($data['width']) || empty($data['height']) || !is_string($data['url']) || !is_numeric($data['width']) || !is_numeric($data['height'])) {
                            return $matches[0];
                        }

                        $title = !empty($data['title']) && is_string($data['title']) ? $data['title'] : '';

                        return '<a href="'.$url.'"><img src="'.htmlspecialchars($data['url'], ENT_QUOTES, 'UTF-8').'" alt="'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'" width="'.htmlspecialchars($data['width'], ENT_QUOTES, 'UTF-8').'" height="'.htmlspecialchars($data['height'], ENT_QUOTES, 'UTF-8').'" /></a>';

                    case 'video':
                    case 'rich':
                        if (!empty($data['html']) && is_string($data['html'])) {
                            return $data['html'];
                        }
                        break;

                    case 'link':
                        if (!empty($data['title']) && is_string($data['title'])) {
                            return self::createLinkTag($url, $data['title']);
                        }
                        break;

                    default:
                        return $matches[0];
                }
            } else {
                return $matches[0];
            }
        }, $string);

        return $string;
    }

    /**
     * Parse text to find all URLs that are not linked and create A tag.
     *
     * @param string $string     Text to parse
     * @param array  $attributes Optional, additional key/value attributes to include in the A tag
     *
     * @return string
     */
    public static function makeClickableLinks($string, $attributes = [])
    {
        $attr = self::arrayToString($attributes);

        return preg_replace('@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@', '<a href="$1" '.$attr.'>$1</a>', $string);
    }

    /**
     * Dump information about a variable.
     *
     * @param mixed $variable Variable to debug
     *
     * @return void
     */
    public static function debug($variable)
    {
        ob_start();
        var_dump($variable);
        $output = ob_get_clean();
        $maps = ['string' => "/(string\((?P<length>\d+)\)) (?P<value>\"(?<!\\\).*\")/i", 'array' => "/\[\"(?P<key>.+)\"(?:\:\"(?P<class>[a-z0-9_\\\]+)\")?(?:\:(?P<scope>public|protected|private))?\]=>/Ui", 'countable' => "/(?P<type>array|int|string)\((?P<count>\d+)\)/", 'resource' => "/resource\((?P<count>\d+)\) of type \((?P<class>[a-z0-9_\\\]+)\)/", 'bool' => "/bool\((?P<value>true|false)\)/", 'float' => "/float\((?P<value>[0-9\.]+)\)/", 'object' => "/object\((?P<class>\S+)\)\#(?P<id>\d+) \((?P<count>\d+)\)/i"];
        foreach ($maps as $function => $pattern) {
            $output = preg_replace_callback($pattern, function ($matches) use ($function) {
                switch ($function) {
                    case 'string':
                        $matches['value'] = htmlspecialchars($matches['value']);

                        return '<span style="color: #0000FF;">string</span>(<span style="color: #1287DB;">'.$matches['length'].')</span> <span style="color: #6B6E6E;">'.$matches['value'].'</span>';

                    case 'array':
                        $key = '<span style="color: #008000;">"'.$matches['key'].'"</span>';
                        $class = '';
                        $scope = '';
                        if (isset($matches['class']) && !empty($matches['class'])) {
                            $class = ':<span style="color: #4D5D94;">"'.$matches['class'].'"</span>';
                        }
                        if (isset($matches['scope']) && !empty($matches['scope'])) {
                            $scope = ':<span style="color: #666666;">'.$matches['scope'].'</span>';
                        }

                        return '['.$key.$class.$scope.']=>';

                    case 'countable':
                        $type = '<span style="color: #0000FF;">'.$matches['type'].'</span>';
                        $count = '(<span style="color: #1287DB;">'.$matches['count'].'</span>)';

                        return $type.$count;

                    case 'bool':
                        return '<span style="color: #0000FF;">bool</span>(<span style="color: #0000FF;">'.$matches['value'].'</span>)';

                    case 'float':
                        return '<span style="color: #0000FF;">float</span>(<span style="color: #1287DB;">'.$matches['value'].'</span>)';

                    case 'resource':
                        return '<span style="color: #0000FF;">resource</span>(<span style="color: #1287DB;">'.$matches['count'].'</span>) of type (<span style="color: #4D5D94;">'.$matches['class'].'</span>)';

                    case 'object':
                        return '<span style="color: #0000FF;">object</span>(<span style="color: #4D5D94;">'.$matches['class'].'</span>)#'.$matches['id'].' (<span style="color: #1287DB;">'.$matches['count'].'</span>)';

                }
            }, $output);
        }
        $header = '';
        list($debugfile) = debug_backtrace();
        if (!empty($debugfile['file'])) {
            $header = '<h4 style="border-bottom:1px solid #bbb;font-weight:bold;margin:0 0 10px 0;padding:3px 0 10px 0">'.$debugfile['file'].'</h4>';
        }
        echo '<pre style="background-color: #CDDCF4;border: 1px solid #bbb;border-radius: 4px;-moz-border-radius:4px;-webkit-border-radius\:4px;font-size:12px;line-height:1.4em;margin:30px;padding:7px">'.$header.$output.'</pre>';
    }
}
