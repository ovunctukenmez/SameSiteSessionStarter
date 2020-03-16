<?php
/**
 * @author Ovunc Tukenmez <ovunct@live.com>
 * @version 1.0.0
 * Date: 15.03.2020
 *
 * This class adds samesite parameter for cookies created by session_start function.
 * The browser agent is also checked against incompatible list of browsers.
 */

class SameSiteSessionStarter
{
    static $samesite = 'None';
    static $is_secure = true;

    public static function session_start()
    {

        if (version_compare('7.3.0', phpversion()) == 1) {
            @session_start();

            if (self::isBrowserSameSiteCompatible($_SERVER['HTTP_USER_AGENT'])) {
                $headers_list = headers_list();
                foreach ($headers_list as $_header) {
                    if (strpos($_header, 'Set-Cookie: ' . session_name()) === 0) {
                        $additional_labels = array();

                        $same_site = self::$samesite;
                        $is_secure = ($same_site == 'None' ? true : self::$is_secure);

                        if ($is_secure && boolval(ini_get('session.cookie_secure')) == false){
                            $additional_labels[] = '; Secure';
                        }
                        $additional_labels[] = '; SameSite=' . $same_site;

                        header($_header . implode('',$additional_labels));
                        break;
                    }
                }
            }
        } else {
            $same_site = self::$samesite;

            if (self::isBrowserSameSiteCompatible($_SERVER['HTTP_USER_AGENT']) == false) {
                $same_site = '';
            }
            $is_secure = ($same_site == 'None' ? true : self::$is_secure);

            ini_set('cookie_samesite', $same_site);
            ini_set('cookie_secure ', $is_secure);

            @session_start();
        }
    }

    public static function isBrowserSameSiteCompatible($user_agent)
    {
        // check Chrome
        $regex = '#(CriOS|Chrome)/([0-9]*)#';
        if (preg_match($regex, $user_agent, $matches) == true) {
            $version = $matches[2];
            if ($version < 67) {
                return false;
            }
        }

        // check iOS
        $regex = '#iP.+; CPU .*OS (\d+)_\d#';
        if (preg_match($regex, $user_agent, $matches) == true) {
            $version = $matches[1];
            if ($version < 13) {
                return false;
            }
        }

        // check MacOS 10.14
        $regex = '#Macintosh;.*Mac OS X (\d+)_(\d+)_.*AppleWebKit#';
        if (preg_match($regex, $user_agent, $matches) == true) {
            $version_major = $matches[1];
            $version_minor = $matches[2];
            if ($version_major == 10 && $version_minor == 14) {
                // check Safari
                $regex = '#Version\/.* Safari\/#';
                if (preg_match($regex, $user_agent) == true) {
                    return false;
                }
                // check Embedded Browser
                $regex = '#AppleWebKit\/[\.\d]+ \(KHTML, like Gecko\)#';
                if (preg_match($regex, $user_agent) == true) {
                    return false;
                }
            }
        }

        // check UC Browser
        $regex = '#UCBrowser/(\d+)\.(\d+)\.(\d+)#';
        if (preg_match($regex, $user_agent, $matches) == true) {
            $version_major = $matches[1];
            $version_minor = $matches[2];
            $version_build = $matches[3];
            if ($version_major == 12 && $version_minor == 13 && $version_build == 2) {
                return false;
            }
        }

        return true;
    }
}
