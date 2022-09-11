<?php

namespace stccorp\yahoo-oauth2;

class YahooTokens
{
    var $tokens;

    function __construct($json_data)
    {
        $this->tokens = json_decode($json_data, true);
    }

    function hasData()
    {
        return (strlen($this->getAccessToken()) > 0);
    }

    function getAccessToken()
    {
        return $this->tokens['access_token'];
    }

    function getRefreshToken()
    {
        //refresh_token, there are no regresh tokens anymore
        return $this->tokens['refresh_token'];
    }

    function expired()
    {
        return (time() > (int)$this->tokens['expires_in']);
    }

    function toJson()
    {
        return json_encode($this->tokens);
    }

}

class YahooClient
{
    var $scope='';
    var $client_id;
    var $client_secret;
    var $redirect_url;
    var $tokens;
    var $nonce='';

    const AUTH_ENDPOINT = 'https://api.login.yahoo.com/oauth2/request_auth';
    const TOKEN_ENDPOINT = 'https://api.login.yahoo.com/oauth2/get_token';
    const USERINFO_ENDPOINT = 'https://api.login.yahoo.com/openid/v1/userinfo';

    function __construct()
    {
    }

    static function generateNonce()
    {
        return bin2hex(random_bytes(128/8));
    }

    function setNonce($v)
    {
        $this->nonce = $v;
    }

    function setTokens(YahooTokens $t)
    {
        $this->tokens = $t;
    }

    function getTokens()
    {
        return $this->tokens;
    }

    function setClientId($v)
    {
        $this->client_id = $v;
    }

    function setClientSecret($v)
    {
        $this->client_secret = $v;
    }

    function setRedirectUri($v)
    {
        $this->redirect_url = $v;
    }

   // function setAccessToken($v)
   // {
   //     $this->access_token = $v;
   // }

    function addScope($v)
    {
        $this->scope = $v;
    }

    public function createAuthUrl()
    {

        $url = YahooClient::AUTH_ENDPOINT . "?client_id=" . $this->client_id . "&scope=" . urlencode($this->scope ?? '') . "&nonce=" . $this->nonce . "&prompt=consent&&response_type=code&redirect_uri=" . ($this->redirect_url ?? '');

        return $url;
    }

    public function fetchAccessTokenWithAuthCode($auth_code)
    {
        //echo "REDEEM CODE";
        $arraytoreturn = array();
        $output = "";

        $uri = YahooClient::TOKEN_ENDPOINT;
        // echo "<BR>" . $uri;
        $data = "client_id=" . $this->client_id
            . "&redirect_uri=" . urlencode($this->redirect_url)
            . "&client_secret=" . urlencode($this->client_secret)
            . "&code=" . $auth_code
            . "&grant_type=authorization_code";
        // echo $data;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $uri);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_VERBOSE, true);//for debugging
        // curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded',
        ));
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        var_dump($output);

        if ($output === FALSE) {
            echo "Error sending" . " " . curl_error($ch);
            curl_close($ch);
            echo json_encode(array());
        } else {
            //echo "Done";
            curl_close($ch);
            //echo $output;
        }


        $out2 = json_decode($output, true);

        $arraytoreturn = array('refresh_token' => $out2['refresh_token'],
                               'access_token' => $out2['access_token'],
                               'expires_in' => (time() + (int)$out2['expires_in']));

        return new YahooTokens(json_encode($arraytoreturn));

    }

    public function getAccessToken()
    {
        if (!$this->tokens->expired())
            return $this->tokens;

        else {
            return $this->refreshOauthToken();
        }

    }

    public function refreshTokens()
    {
        //$this->tokens = $tokens;

        if (!$this->tokens->expired())
            return array(false, $this->tokens);//false= no need to refresh

        else
            return array(true, $this->refreshOauthToken());

    }


    // refreshOauthToken()

    // Attempts to refresh an oAuth token
    // Pass in the refresh token obtained from a previous oAuth request.
    // Returns the new oAuth token and an expiry time in seconds from now (usually 3600 but may vary in future).

    protected function refreshOauthToken()
    {
        $arraytoreturn = [];
        $output = "";

        $uri = YahooClient::TOKEN_ENDPOINT;
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $uri);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/x-www-form-urlencoded',
            ));
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

            $data = "client_id=" . $this->client_id . "&redirect_uri=" . urlencode($this->redirect_url) . "&client_secret=" . urlencode($this->client_secret) . "&refresh_token=" . $this->tokens->getRefreshToken() . "&grant_type=refresh_token";
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            $output = curl_exec($ch);
        } catch (Exception $e) {
        }

        $out2 = json_decode($output, true);
        if (array_key_exists('access_token', $out2)) {
            $arraytoreturn = array('access_token' => $out2['access_token'], 'refresh_token' => $out2['refresh_token'], 'expires_in' => (time() + (int)$out2['expires_in']));
            $this->tokens = new YahooTokens(json_encode($arraytoreturn));
        } else {
            $this->tokens = new YahooTokens('');
        }

        return $this->tokens;

    }

    function getUserInfo()
    {
        return $this->httpGet(YahooClient::USERINFO_ENDPOINT);

    }

    private function httpGet($url)
    {
        // echo $url;
        // echo "token=" . $this->access_token;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_VERBOSE, true);//for debugging
        // curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            //  'Content-Type: application/json',
            'Authorization: Bearer ' .  $this->tokens->getAccessToken(),
        ));

        $output = curl_exec($ch);


        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);
        return $output;
    }


}
