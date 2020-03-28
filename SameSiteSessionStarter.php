<?php
/**
 * @author Ovunc Tukenmez <ovunct@live.com>
 * @version 1.3.0
 * Date: 28.03.2020
 *
 * This class adds samesite parameter for cookies created by session_start function.
 * The browser agent is also checked against incompatible list of browsers.
 */

class SameSiteSessionStarter
{
    static $samesite = 'None';
    static $is_secure = true;
    static private $_is_browser_compatible = array();

    public static function session_start()
    {
        if (version_compare('7.3.0', phpversion()) == 1) {
            @session_start();

            if (self::isBrowserSameSiteCompatible($_SERVER['HTTP_USER_AGENT'])) {
                $new_headers = array();
                $headers_list = array_reverse(headers_list());
                $is_modified = false;
                foreach ($headers_list as $_header) {
                    if (!$is_modified && strpos($_header, 'Set-Cookie: ' . session_name()) === 0) {
                        $additional_labels = array();

                        $same_site = self::$samesite;
                        $is_secure = ($same_site == 'None' ? true : self::$is_secure);

                        if ($is_secure && boolval(ini_get('session.cookie_secure')) == false){
                            $additional_labels[] = '; Secure';
                        }

                        $new_label = '; SameSite=' . $same_site;
                        if (strpos($_header,$new_label) === false){
                            $additional_labels[] = $new_label;
                        }

                        $_header = $_header . implode('',$additional_labels);
                        $is_modified = true;
                    }
                    $new_headers[] = $_header;
                }

                header_remove();
                $new_headers = array_reverse($new_headers);
                foreach ($new_headers as $_header){
                    header($_header,false);
                }
            }
        } else {
            $same_site = self::$samesite;

            if (self::isBrowserSameSiteCompatible($_SERVER['HTTP_USER_AGENT']) == false) {
                $same_site = '';
            }
            $is_secure = ($same_site == 'None' ? true : self::$is_secure);

            ini_set('session.cookie_samesite', $same_site);
            ini_set('session.cookie_secure', $is_secure);

            @session_start();
        }
    }

    private static function _setIsBrowserCompatible($user_agent_key,$value){
        self::$_is_browser_compatible[$user_agent_key] = $value;
    }
    private static function _getIsBrowserCompatible($user_agent_key){
        if (isset(self::$_is_browser_compatible[$user_agent_key])){
            return self::$_is_browser_compatible[$user_agent_key];
        }
        return null;
    }

    public static function isBrowserSameSiteCompatible($user_agent)
    {
        $user_agent_key = md5($user_agent);
        $self_check = self::_getIsBrowserCompatible($user_agent_key);
        if ($self_check !== null){
            return $self_check;
        }

        // check Chrome
        $regex = '#(CriOS|Chrome)/([0-9]*)#';
        if (preg_match($regex, $user_agent, $matches) == true) {
            $version = $matches[2];
            if ($version < 67) {
                self::_setIsBrowserCompatible($user_agent_key,false);
                return false;
            }
        }

        // check iOS
        $regex = '#iP.+; CPU .*OS (\d+)_\d#';
        if (preg_match($regex, $user_agent, $matches) == true) {
            $version = $matches[1];
            if ($version < 13) {
                self::_setIsBrowserCompatible($user_agent_key,false);
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
                    self::_setIsBrowserCompatible($user_agent_key,false);
                    return false;
                }
                // check Embedded Browser
                $regex = '#AppleWebKit\/[\.\d]+ \(KHTML, like Gecko\)#';
                if (preg_match($regex, $user_agent) == true) {
                    self::_setIsBrowserCompatible($user_agent_key,false);
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
                self::_setIsBrowserCompatible($user_agent_key,false);
                return false;
            }
        }

        self::_setIsBrowserCompatible($user_agent_key,true);
        return true;
    }
}
