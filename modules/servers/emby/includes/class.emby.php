<?php 
if( !defined("WHMCS") ) 
{
    exit( "This file cannot be accessed directly" );
}


class EmbyApiClient
{
    private $serverAddress = NULL;
    private $username = NULL;
    private $password = NULL;
    private $headers = [  ];
    public $accessToken = NULL;

    public function __construct($serverAddress = "", $username = "", $pwd = "")
    {
        $this->serverAddress = $serverAddress;
        $this->username = $username;
        $this->password = $pwd;
        $this->accessToken = $this->getAccessToken();
    }

    public function setRequestHeaders()
    {
        if( $this->accessToken ) 
        {
            $this->headers["token"] = "X-MediaBrowser-Token: " . $this->accessToken;
        }

        $this->headers["type"] = "Content-Type: application/json";
        $this->headers["auth"] = "X-Emby-Authorization: MediaBrowser Client=Android, Device=Samsung Galaxy SIII, DeviceId=xxx, Version=1.0.0.0";
    }

    public function getAccessToken()
    {
        $token = "";
        $res = $this->authenticateUser($this->username, $this->password);
        if( isset($res["response"]["AccessToken"]) && !empty($res["response"]["AccessToken"]) ) 
        {
            $token = $res["response"]["AccessToken"];
        }

        return $token;
    }

    public function getUrl($name, $params = [  ])
    {
        $this->url = $this->serverAddress . "/emby/";
        $this->url = $this->url . $name;
        if( !empty($params) ) 
        {
            $this->url = $this->url . "?" . implode("&", $params);
        }

        return $this->url;
    }

    public function createUser($name)
    {
        $this->params = [ "url" => $this->getUrl("Users/New"), "name" => $name ];
        return $this->curlPostRequest($this->params);
    }

    public function setUserPassword($userid, $newpw, $currentpw = "")
    {
        if( empty($userid) || empty($newpw) ) 
        {
            return [ "status" => "error", "message" => "Required params are missing." ];
        }

        $url = "Users/" . $userid . "/Password";
        $this->params = [ "url" => $this->getUrl($url), "CurrentPw" => $currentpw, "NewPw" => $newpw ];
        return $this->curlPostRequest($this->params);
    }

    public function deleteUser($userId)
    {
        if( empty($userId) ) 
        {
            return [ "status" => "error", "message" => "Userid is required." ];
        }

        $url = "Users/" . $userId;
        $this->params = [ "url" => $this->getUrl($url) ];
        return $this->curlDeleteRequest($this->params);
    }

    public function updateUserPolicy($userId, $policies)
    {
        if( empty($userId) ) 
        {
            return [ "status" => "error", "message" => "Userid is required." ];
        }

        if( empty($policies) ) 
        {
            return [ "status" => "error", "message" => "Required params are empty." ];
        }

        $url = "Users/" . $userId . "/Policy";
        $this->params = array_merge([ "url" => $this->getUrl($url) ], $policies);
        return $this->curlPostRequest($this->params);
    }

    public function authenticateUser($name, $pwd)
    {
        $this->params = [ "url" => $this->getUrl("Users/AuthenticateByName"), "username" => $name, "pw" => $pwd, "password" => sha1($pwd), "passwordMd5" => md5($pwd) ];
        return $this->curlPostRequest($this->params);
    }

    public function logout()
    {
        $this->params = [ "url" => $this->getUrl("Sessions/Logout") ];
        return $this->curlPostRequest($this->params);
    }

    public function embyConnectUser($id_user, $connect_email)
    {
        if( empty($id_user) || empty($connect_email) ) 
        {
            return [ "status" => "error", "message" => "Required params are empty." ];
        }

        $this->params = [ "url" => $this->getUrl("Users/" . $id_user . "/Connect/Link"), "ConnectUsername" => $connect_email ];
        return $this->curlPostRequest($this->params);
    }

    public function curlPostRequest($params)
    {
        if( empty($params) || !isset($params["url"]) || empty($params["url"]) ) 
        {
            return [ "status" => "error", "message" => "Required parameters are missing." ];
        }

        $url = $params["url"];
        unset($params["url"]);
        $this->setRequestHeaders();
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        if( !empty($this->headers) ) 
        {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);
        }

        $data_string = json_encode($params);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        $curl_response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $return = [  ];
        if( $httpcode == 200 ) 
        {
            if( !empty($curl_response) ) 
            {
                $res = json_decode($curl_response, true);
                $return = [ "status" => "success", "response" => $res ];
            }
            else
            {
                $return = [ "status" => "error", "message" => "Empty response from api." ];
            }

        }
        else
        {
            if( $httpcode == 204 ) 
            {
                $return = [ "status" => "success", "response" => [  ] ];
            }
            else
            {
                $return = [ "status" => "error", "message" => "HTTPCODE: " . $httpcode . ". Error: " . $curl_response, "response" => curl_error($curl) ];
            }

        }

        curl_close($curl);
        $params["url"] = $url;
        $params["headers"] = $this->headers;
        logModuleCall("emby", "Curl Post Request", $params, $return, "");
        return $return;
    }

    public function curlDeleteRequest($params)
    {
        if( empty($params) || !isset($params["url"]) || empty($params["url"]) ) 
        {
            return [ "status" => "error", "message" => "Required parameters are missing." ];
        }

        $url = $params["url"];
        unset($params["url"]);
        $this->setRequestHeaders();
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        if( !empty($this->headers) ) 
        {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);
        }

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
        if( !empty($params) ) 
        {
            $data_string = json_encode($params);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        }

        $curl_response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $return = [  ];
        if( $httpcode == 200 ) 
        {
            if( !empty($curl_response) ) 
            {
                $res = json_decode($curl_response, true);
                $return = [ "status" => "success", "response" => $res ];
            }
            else
            {
                $return = [ "status" => "error", "message" => "Empty response from api." ];
            }

        }
        else
        {
            if( $httpcode == 204 ) 
            {
                $return = [ "status" => "success", "response" => [  ] ];
            }
            else
            {
                $return = [ "status" => "error", "message" => "HTTPCODE: " . $httpcode . ". Error: " . curl_error($curl), "response" => $curl_response ];
            }

        }

        curl_close($curl);
        $params["url"] = $url;
        $params["headers"] = $this->headers;
        logModuleCall("emby", "Curl Delete Request", $params, $return, "");
        return $return;
    }

    public function curlGetRequest($params)
    {
        if( empty($params) || !isset($params["url"]) || empty($params["url"]) ) 
        {
            return [ "status" => "error", "message" => "Required parameters are missing." ];
        }

        $url = $params["url"];
        unset($params["url"]);
        $this->setRequestHeaders();
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        if( !empty($this->headers) ) 
        {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);
        }

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HEADER, false);
        $curl_response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $return = [  ];
        if( $httpcode == 200 ) 
        {
            if( !empty($curl_response) ) 
            {
                $res = json_decode($curl_response, true);
                $return = [ "status" => "success", "response" => $res ];
            }
            else
            {
                $return = [ "status" => "error", "message" => "Empty response from api." ];
            }

        }
        else
        {
            if( $httpcode == 204 ) 
            {
                $return = [ "status" => "success", "response" => [  ] ];
            }
            else
            {
                $return = [ "status" => "error", "message" => "HTTPCODE: " . $httpcode . ". Error: " . $curl_response, "response" => curl_error($curl) ];
            }

        }

        curl_close($curl);
        $params["url"] = $url;
        $params["headers"] = $this->headers;
        logModuleCall("emby", "Curl Post Request", $params, $return, "");
        return $return;
    }

}


?>