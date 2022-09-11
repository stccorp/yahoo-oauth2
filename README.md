# yahoo-oauth2

create a yconfig.php

```sh
require_once 'vendor/autoload.php';

use stccorp\YahooAuth\YahooClient;

const YAHOO_CLIENT_ID = 'Your client Id';
const YAHOO_CLIENT_SECRET = 'Your Secret';
const YAHOO_REDIRECT_URI =  'Your Redirect Url';

$_SESSION['nonce'] = bin2hex(random_bytes(128/8));//so I can check the input later

$yahoo_client = new YahooClient();
$yahoo_client->setClientId(YAHOO_CLIENT_ID);
$yahoo_client->setClientSecret(YAHOO_CLIENT_SECRET);
$yahoo_client->setRedirectUri(YAHOO_REDIRECT_URI);
$yahoo_client->addScope('openid');
$yahoo_client->setNonce($_SESSION['nonce']);
```

You will have a page with the Yahoo login button , let say index.php
```sh
require_once 'env/yconfig.php';

$yahoo_login_btn = '<a alt="Sign in with Yahoo" href="' . filter_var($yahoo_client->createAuthUrl(), FILTER_SANITIZE_URL) . '"><img src="/assets/img/btn_yahoo_rectangle.png" width="200"/></a>';
```


Then you will need a yahoo_callback.php
```sh
require_once 'env/yconfig.php';

if (strlen($_GET['code'] ?? '') > 0) {
  $token = $yahoo_client->fetchAccessTokenWithAuthCode($code);
  
   if (!$token->hasData()) {
      die("No Data");
   }
   
   $yahoo_client->setTokens($token);
   
   $data_json = $yahoo_client->getUserInfo();
   $data = json_decode($data_json);
}

```

