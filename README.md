# SameSiteSessionStarter
This PHP class enables samesite supported php session by modifying header created by session_start function.
The browser agent is also checked against incompatible list of browsers.

## USAGE

start samesite none php session
```
require_once 'SameSiteSessionStarter.php';
SameSiteSessionStarter::session_start();
```

start samesite strict secure php session
```
require_once 'SameSiteSessionStarter.php';
SameSiteSessionStarter::$samesite = 'Strict';
SameSiteSessionStarter::$is_secure = true;
SameSiteSessionStarter::session_start();
```

## Composer

```
composer require ovunctukenmez/samesite-session-starter
```

## NOTES
SameSite=None (default setting) works only with Secure cookies.  
So please make sure to use https protocol to start session correctly. 

If you have previous started session with old settings, you need to clear its cookie first so new cookie can be set with the session_start() function.   
Here is an example to clear previous session cookie programmatically with [SameSiteCookieSetter](https://github.com/ovunctukenmez/SameSiteCookieSetter).
                                                               
```
// https://github.com/ovunctukenmez/SameSiteCookieSetter
require_once 'SameSiteCookieSetter/SameSiteCookieSetter.php';

session_start();

$params = session_get_cookie_params();
$samesite_session_cookie_params = array(
    'samesite' => 'None',
    'secure' => true,
    'expires' => time() - 3600,
    'path' => $params['path'],
    'domain' => $params['domain'],
    'httponly' => isset($params['httponly']) ? $params['httponly'] : false
);

SameSiteCookieSetter::setcookie(session_name(),session_id(),$samesite_session_cookie_params);
```

To set samesite cookies, you can use [SameSiteCookieSetter](https://github.com/ovunctukenmez/SameSiteCookieSetter).