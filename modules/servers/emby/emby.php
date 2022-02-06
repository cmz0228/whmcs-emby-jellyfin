<?php 
if( !defined("WHMCS") ) 
{
    exit( "This file cannot be accessed directly" );
}

function emby_MetaData()
{
    return [ "DisplayName" => "Embyervers", "APIVersion" => "1.2", "RequiresServer" => true, "DefaultNonSSLPort" => "8096", "DefaultSSLPort" => "443" ];
}

function emby_ConfigOptions()
{
    try
    {
    }
    catch( Exception $e ) 
    {
        logActivity("Emby Error: Unable to create custom tables. " . $e->getMessage());
    }
    if( !WHMCS\Database\Capsule::schema()->hasTable("mod_emby_connect_user") ) 
    {
        WHMCS\Database\Capsule::schema()->create("mod_emby_connect_user", function($table)
{
    $table->increments("id");
    $table->integer("serviceid");
    $table->string("emby_userid");
}

);
    }

    if( !WHMCS\Database\Capsule::schema()->hasTable("mod_emby_connect_passwords") ) 
    {
        WHMCS\Database\Capsule::schema()->create("mod_emby_connect_passwords", function($table)
{
    $table->increments("id");
    $table->integer("clientid");
    $table->string("password");
}

);
    }

    

    $configarray = [ "EmbyConnect" => [ "FriendlyName" => "EmbyConnect", "Type" => "yesno", "Description" => "EmbyConnect" ], "IsAdministrator" => [ "FriendlyName" => "Administrator", "Type" => "yesno", "Description" => "Tick to make this user an admin" ], "IsDisabled" => [ "FriendlyName" => "Disabled", "Type" => "yesno", "Description" => "Tick to make this user disabled " ], "EnableUserPreferenceAccess" => [ "FriendlyName" => "Enable User Preference Access", "Type" => "yesno", "Description" => "Tick to enable user preference access", "Default" => "on" ], "EnableRemoteControlOfOtherUsers" => [ "FriendlyName" => "Enable Remote Control Of Other Users", "Type" => "yesno", "Description" => "Tick to enable remote control of other users" ], "EnableSharedDeviceControl" => [ "FriendlyName" => "Enable Shared Device Control", "Type" => "yesno", "Description" => "Tick to enable shared device control" ], "EnableRemoteAccess" => [ "FriendlyName" => "Enable Remote Access", "Type" => "yesno", "Description" => "Tick to enable remote access", "Default" => "on" ], "EnableLiveTvManagement" => [ "FriendlyName" => "Enable Live Tv Management", "Type" => "yesno", "Description" => "Tick to enable live TV management", "Default" => "on" ], "EnableLiveTvAccess" => [ "FriendlyName" => "Enable Live Tv Access", "Type" => "yesno", "Description" => "Tick to enable live TV access", "Default" => "on" ], "EnableMediaPlayback" => [ "FriendlyName" => "Enable Media Playback", "Type" => "yesno", "Description" => "Tick to enable media playback", "Default" => "on" ], "EnableAudioPlaybackTranscoding" => [ "FriendlyName" => "Enable Audio Playback Transcoding", "Type" => "yesno", "Description" => "Tick to enable audio playback transcoding" ], "EnableVideoPlaybackTranscoding" => [ "FriendlyName" => "Enable Video Playback Transcoding", "Type" => "yesno", "Description" => "Tick to enable video playback transcoding" ], "EnablePlaybackRemuxing" => [ "FriendlyName" => "Enable Playback Remuxing", "Type" => "yesno", "Description" => "Tick to enable playback remuxing", "Default" => "on" ], "EnableContentDeletion" => [ "FriendlyName" => "Enable Content Deletion", "Type" => "yesno", "Description" => "Tick to enable content deletion" ], "EnableContentDownloading" => [ "FriendlyName" => "Enable Content Downloading", "Type" => "yesno", "Description" => "Tick to enable content downloading" ], "EnableSyncTranscoding" => [ "FriendlyName" => "Enable Sync Transcoding", "Type" => "yesno", "Description" => "Tick to enable sync transcoding" ], "EnableMediaConversion" => [ "FriendlyName" => "Enable Media Conversion", "Type" => "yesno", "Description" => "Tick to enable media conversion" ], "EnableAllDevices" => [ "FriendlyName" => "Enable All Devices", "Type" => "yesno", "Description" => "Tick to enable all devices", "Default" => "on" ], "EnableAllChannels" => [ "FriendlyName" => "Enable All Channels", "Type" => "yesno", "Description" => "Tick to enable all channels", "Default" => "on" ], "EnableAllFolders" => [ "FriendlyName" => "Enable All Folders", "Type" => "yesno", "Description" => "Tick to enable all folders", "Default" => "on" ], "ActiveLoginAttemptCount" => [ "FriendlyName" => "Active Login Attempt Count", "Type" => "text", "Size" => "30", "Description" => "", "Default" => "0" ], "EnablePublicSharing" => [ "FriendlyName" => "Enable Public Sharing", "Type" => "yesno", "Description" => "Tick to enable public sharing" ], "SimultaneousStreamLimit" => [ "FriendlyName" => "Simultaneous Stream Limit", "Type" => "text", "Size" => "30", "Description" => "Streams at once Limit", "Default" => "2" ], "IsHidden" => [ "FriendlyName" => "Is Hidden", "Type" => "yesno", "Description" => "Tick to hide the user from login screen", "Default" => "on" ] ];
    return $configarray;
}

function emby_CreateAccount(array $params)
{
    

    include_once(dirname(__FILE__) . "/includes/class.emby.php");
    $hostname = $params["serverhttpprefix"] . "://" . $params["serverhostname"];
    if( !empty($params["serverport"]) ) 
    {
        $hostname .= ":" . $params["serverport"];
    }

    $embyemail = $params["customfields"]["emby_email"];
    $username = $params["serverusername"];
    $password = $params["serverpassword"];
    $apiClient = new EmbyApiClient($hostname, $username, $password);
    $email = $params["customfields"]["emby_email"];
    if( isset($params["customfields"]["emby_username"]) && isset($params["customfields"]["emby_password"]) ) 
    {
        $embyUsername = $params["customfields"]["emby_username"];
        $embyPassword = $params["customfields"]["emby_password"];
        if( empty($email) || empty($embyemail) ) 
        {
            return "Emby email field cannot be empty.";
        }

        WHMCS\Database\Capsule::table("tblhosting")->where("id", $params["serviceid"])->update([ "username" => $email, "password" => encrypt($embyPassword) ]);
    }
    else
    {
        $embyUsername = $params["email"];
        $embyPassword = $params["password"];
        if( empty($embyUsername) ) 
        {
            $embyUsername = emby_generateRandStr(8);
        }

        WHMCS\Database\Capsule::table("tblhosting")->where("id", $params["serviceid"])->update([ "username" => $embyemail ]);
    }

    $res = $apiClient->createUser($embyemail);
    if( $res["status"] == "success" ) 
    {
        $emby_userid = $res["response"]["Id"];
        $userpolicies = [ "IsHidden" => (isset($params["configoption24"]) && $params["configoption24"] == "on" ? 1 : 0), "IsAdministrator" => (isset($params["configoption2"]) && $params["configoption2"] == "on" ? 1 : 0), "IsDisabled" => (isset($params["configoption3"]) && $params["configoption3"] == "on" ? 1 : 0), "EnableUserPreferenceAccess" => (isset($params["configoption4"]) && $params["configoption4"] == "on" ? 1 : 0), "EnableRemoteControlOfOtherUsers" => (isset($params["configoption5"]) && $params["configoption5"] == "on" ? 1 : 0), "EnableSharedDeviceControl" => (isset($params["configoption6"]) && $params["configoption6"] == "on" ? 1 : 0), "EnableRemoteAccess" => (isset($params["configoption7"]) && $params["configoption7"] == "on" ? 1 : 0), "EnableLiveTvManagement" => (isset($params["configoption8"]) && $params["configoption8"] == "on" ? 1 : 0), "EnableLiveTvAccess" => (isset($params["configoption9"]) && $params["configoption9"] == "on" ? 1 : 0), "EnableMediaPlayback" => (isset($params["configoption10"]) && $params["configoption10"] == "on" ? 1 : 0), "EnableAudioPlaybackTranscoding" => (isset($params["configoption11"]) && $params["configoption11"] == "on" ? 1 : 0), "EnableVideoPlaybackTranscoding" => (isset($params["configoption12"]) && $params["configoption12"] == "on" ? 1 : 0), "EnablePlaybackRemuxing" => (isset($params["configoption13"]) && $params["configoption13"] == "on" ? 1 : 0), "EnableContentDeletion" => (isset($params["configoption14"]) && $params["configoption14"] == "on" ? 1 : 0), "EnableContentDownloading" => (isset($params["configoption15"]) && $params["configoption15"] == "on" ? 1 : 0), "EnableSyncTranscoding" => (isset($params["configoption16"]) && $params["configoption16"] == "on" ? 1 : 0), "EnableMediaConversion" => (isset($params["configoption17"]) && $params["configoption17"] == "on" ? 1 : 0), "EnableAllDevices" => (isset($params["configoption18"]) && $params["configoption18"] == "on" ? 1 : 0), "EnableAllChannels" => (isset($params["configoption19"]) && $params["configoption19"] == "on" ? 1 : 0), "EnableAllFolders" => (isset($params["configoption20"]) && $params["configoption20"] == "on" ? 1 : 0), "ActiveLoginAttemptCount" => (isset($params["configoption21"]) ? $params["configoption21"] : 0), "EnablePublicSharing" => (isset($params["configoption22"]) && $params["configoption22"] == "on" ? 1 : 0), "SimultaneousStreamLimit" => (isset($params["configoption23"]) ? $params["configoption23"] : 0) ];
        if( isset($params["configoption1"]) && $params["configoption1"] == "on" ) 
        {
            $apiClient->embyConnectUser($emby_userid, $email);
        }

        $apiClient->setUserPassword($emby_userid, $embyPassword);
        $apiClient->updateUserPolicy($emby_userid, $userpolicies);
        WHMCS\Database\Capsule::table("mod_emby_connect_user")->where("serviceid", $params["serviceid"])->delete();
        WHMCS\Database\Capsule::table("mod_emby_connect_user")->insert([ "serviceid" => $params["serviceid"], "emby_userid" => $emby_userid ]);
        $ep = emby_encryptPassword($embyPassword);
        $up = (isset($ep["password"]) ? $ep["password"] : "");
        WHMCS\Database\Capsule::table("mod_emby_connect_passwords")->insert([ "clientid" => $params["userid"], "password" => $up ]);
        logModuleCall("emby", "emby_CreateAccount", $name, "User account created successfully.", "");
        $apiClient->logout();
        return "success";
    }

    logModuleCall("emby", "emby_CreateAccount", $name, $res["message"], "");
    return $res["message"];
}

function emby_SuspendAccount(array $params)
{
    include_once(dirname(__FILE__) . "/includes/class.emby.php");
    $hostname = $params["serverhttpprefix"] . "://" . $params["serverhostname"];
    if( !empty($params["serverport"]) ) 
    {
        $hostname .= ":" . $params["serverport"];
    }

    $username = $params["serverusername"];
    $password = $params["serverpassword"];
    $name = $params["username"];
    $emby_userid = WHMCS\Database\Capsule::table("mod_emby_connect_user")->where("serviceid", $params["serviceid"])->value("emby_userid");
    if( !empty($emby_userid) ) 
    {
        $apiClient = new EmbyApiClient($hostname, $username, $password);
        $userpolicies = [ "IsHidden" => (isset($params["configoption24"]) && $params["configoption24"] == "on" ? 1 : 0), "IsAdministrator" => (isset($params["configoption2"]) && $params["configoption2"] == "on" ? 1 : 0), "IsDisabled" => 1, "EnableUserPreferenceAccess" => (isset($params["configoption4"]) && $params["configoption4"] == "on" ? 1 : 0), "EnableRemoteControlOfOtherUsers" => (isset($params["configoption5"]) && $params["configoption5"] == "on" ? 1 : 0), "EnableSharedDeviceControl" => (isset($params["configoption6"]) && $params["configoption6"] == "on" ? 1 : 0), "EnableRemoteAccess" => (isset($params["configoption7"]) && $params["configoption7"] == "on" ? 1 : 0), "EnableLiveTvManagement" => (isset($params["configoption8"]) && $params["configoption8"] == "on" ? 1 : 0), "EnableLiveTvAccess" => (isset($params["configoption9"]) && $params["configoption9"] == "on" ? 1 : 0), "EnableMediaPlayback" => (isset($params["configoption10"]) && $params["configoption10"] == "on" ? 1 : 0), "EnableAudioPlaybackTranscoding" => (isset($params["configoption11"]) && $params["configoption11"] == "on" ? 1 : 0), "EnableVideoPlaybackTranscoding" => (isset($params["configoption12"]) && $params["configoption12"] == "on" ? 1 : 0), "EnablePlaybackRemuxing" => (isset($params["configoption13"]) && $params["configoption13"] == "on" ? 1 : 0), "EnableContentDeletion" => (isset($params["configoption14"]) && $params["configoption14"] == "on" ? 1 : 0), "EnableContentDownloading" => (isset($params["configoption15"]) && $params["configoption15"] == "on" ? 1 : 0), "EnableSyncTranscoding" => (isset($params["configoption16"]) && $params["configoption16"] == "on" ? 1 : 0), "EnableMediaConversion" => (isset($params["configoption17"]) && $params["configoption17"] == "on" ? 1 : 0), "EnableAllDevices" => (isset($params["configoption18"]) && $params["configoption18"] == "on" ? 1 : 0), "EnableAllChannels" => (isset($params["configoption19"]) && $params["configoption19"] == "on" ? 1 : 0), "EnableAllFolders" => (isset($params["configoption20"]) && $params["configoption20"] == "on" ? 1 : 0), "ActiveLoginAttemptCount" => (isset($params["configoption21"]) ? $params["configoption21"] : 0), "EnablePublicSharing" => (isset($params["configoption22"]) && $params["configoption22"] == "on" ? 1 : 0), "SimultaneousStreamLimit" => (isset($params["configoption23"]) ? $params["configoption23"] : 0) ];
        $res = $apiClient->updateUserPolicy($emby_userid, $userpolicies);
        if( $res["status"] == "success" ) 
        {
            logModuleCall("emby", "emby_SuspendAccount", $name, "User account suspended successfully.", "");
            $apiClient->logout();
            return "success";
        }

        logModuleCall("emby", "emby_SuspendAccount", $name, $res["message"], "");
        return $res["message"];
    }

    return "Emby userid cannot be empty.";
}

function emby_UnsuspendAccount(array $params)
{
    include_once(dirname(__FILE__) . "/includes/class.emby.php");
    $hostname = $params["serverhttpprefix"] . "://" . $params["serverhostname"];
    if( !empty($params["serverport"]) ) 
    {
        $hostname .= ":" . $params["serverport"];
    }

    $username = $params["serverusername"];
    $password = $params["serverpassword"];
    $name = $params["username"];
    $emby_userid = WHMCS\Database\Capsule::table("mod_emby_connect_user")->where("serviceid", $params["serviceid"])->value("emby_userid");
    if( !empty($emby_userid) ) 
    {
        $apiClient = new EmbyApiClient($hostname, $username, $password);
        $userpolicies = [ "IsHidden" => (isset($params["configoption24"]) && $params["configoption24"] == "on" ? 1 : 0), "IsAdministrator" => (isset($params["configoption2"]) && $params["configoption2"] == "on" ? 1 : 0), "IsDisabled" => 0, "EnableUserPreferenceAccess" => (isset($params["configoption4"]) && $params["configoption4"] == "on" ? 1 : 0), "EnableRemoteControlOfOtherUsers" => (isset($params["configoption5"]) && $params["configoption5"] == "on" ? 1 : 0), "EnableSharedDeviceControl" => (isset($params["configoption6"]) && $params["configoption6"] == "on" ? 1 : 0), "EnableRemoteAccess" => (isset($params["configoption7"]) && $params["configoption7"] == "on" ? 1 : 0), "EnableLiveTvManagement" => (isset($params["configoption8"]) && $params["configoption8"] == "on" ? 1 : 0), "EnableLiveTvAccess" => (isset($params["configoption9"]) && $params["configoption9"] == "on" ? 1 : 0), "EnableMediaPlayback" => (isset($params["configoption10"]) && $params["configoption10"] == "on" ? 1 : 0), "EnableAudioPlaybackTranscoding" => (isset($params["configoption11"]) && $params["configoption11"] == "on" ? 1 : 0), "EnableVideoPlaybackTranscoding" => (isset($params["configoption12"]) && $params["configoption12"] == "on" ? 1 : 0), "EnablePlaybackRemuxing" => (isset($params["configoption13"]) && $params["configoption13"] == "on" ? 1 : 0), "EnableContentDeletion" => (isset($params["configoption14"]) && $params["configoption14"] == "on" ? 1 : 0), "EnableContentDownloading" => (isset($params["configoption15"]) && $params["configoption15"] == "on" ? 1 : 0), "EnableSyncTranscoding" => (isset($params["configoption16"]) && $params["configoption16"] == "on" ? 1 : 0), "EnableMediaConversion" => (isset($params["configoption17"]) && $params["configoption17"] == "on" ? 1 : 0), "EnableAllDevices" => (isset($params["configoption18"]) && $params["configoption18"] == "on" ? 1 : 0), "EnableAllChannels" => (isset($params["configoption19"]) && $params["configoption19"] == "on" ? 1 : 0), "EnableAllFolders" => (isset($params["configoption20"]) && $params["configoption20"] == "on" ? 1 : 0), "ActiveLoginAttemptCount" => (isset($params["configoption21"]) ? $params["configoption21"] : 0), "EnablePublicSharing" => (isset($params["configoption22"]) && $params["configoption22"] == "on" ? 1 : 0), "SimultaneousStreamLimit" => (isset($params["configoption23"]) ? $params["configoption23"] : 0) ];
        $res = $apiClient->updateUserPolicy($emby_userid, $userpolicies);
        if( $res["status"] == "success" ) 
        {
            logModuleCall("emby", "emby_UnsuspendAccount", $name, "User account unsuspended successfully.", "");
            $apiClient->logout();
            return "success";
        }

        logModuleCall("emby", "emby_UnsuspendAccount", $name, $res["message"], "");
        return $res["message"];
    }

    return "Emby userid cannot be empty.";
}

function emby_TerminateAccount(array $params)
{
    include_once(dirname(__FILE__) . "/includes/class.emby.php");
    $hostname = $params["serverhttpprefix"] . "://" . $params["serverhostname"];
    if( !empty($params["serverport"]) ) 
    {
        $hostname .= ":" . $params["serverport"];
    }

    $username = $params["serverusername"];
    $password = $params["serverpassword"];
    $name = $params["username"];
    $emby_userid = WHMCS\Database\Capsule::table("mod_emby_connect_user")->where("serviceid", $params["serviceid"])->value("emby_userid");
    if( !empty($emby_userid) ) 
    {
        $apiClient = new EmbyApiClient($hostname, $username, $password);
        $res = $apiClient->deleteUser($emby_userid);
        if( $res["status"] == "success" ) 
        {
            WHMCS\Database\Capsule::table("mod_emby_connect_user")->where("serviceid", $params["serviceid"])->delete();
            logModuleCall("emby", "emby_TerminateAccount", $name, "User account deleted successfully.", "");
            $apiClient->logout();
            return "success";
        }

        logModuleCall("emby", "emby_TerminateAccount", $name, $res["message"], "");
        return $res["message"];
    }

    return "Emby userid cannot be empty.";
}

function emby_ChangePassword($params)
{
    if( isset($params["customfields"]["emby_password"]) ) 
    {
        $currentPassword = $params["customfields"]["emby_password"];
        if( empty($currentPassword) ) 
        {
            return "Emby password field cannot be empty.";
        }

    }
    else
    {
        $currentPassword = WHMCS\Database\Capsule::table("mod_emby_connect_passwords")->where("clientid", $params["userid"])->value("password");
        if( !empty($currentPassword) ) 
        {
            $dp = emby_decryptPassword($currentPassword);
            $currentPassword = (isset($dp["password"]) ? $dp["password"] : "");
        }

    }

    $updatedPassword = $params["password"];
    $pid = $params["pid"];
    $sid = $params["serviceid"];
    include_once(dirname(__FILE__) . "/includes/class.emby.php");
    $hostname = $params["serverhttpprefix"] . "://" . $params["serverhostname"] . ":" . $params["serverport"];
    $username = $params["serverusername"];
    $password = $params["serverpassword"];
    $emby_userid = WHMCS\Database\Capsule::table("mod_emby_connect_user")->where("serviceid", $params["serviceid"])->value("emby_userid");
    if( !empty($emby_userid) ) 
    {
        $apiClient = new EmbyApiClient($hostname, $username, $password);
        $res = $apiClient->setUserPassword($emby_userid, $updatedPassword, $currentPassword);
        if( $res["status"] == "success" ) 
        {
            if( isset($params["customfields"]["emby_password"]) ) 
            {
                emby_saveCustomFieldValue("emby_password|", $updatedPassword, $pid, $sid);
            }
            else
            {
                WHMCS\Database\Capsule::table("mod_emby_connect_passwords")->where("clientid", $params["userid"])->delete();
                $ep = emby_encryptPassword($updatedPassword);
                $up = (isset($ep["password"]) ? $ep["password"] : "");
                WHMCS\Database\Capsule::table("mod_emby_connect_passwords")->insert([ "clientid" => $params["userid"], "password" => $up ]);
            }

            logModuleCall("emby", "emby_ChangePassword", $updatedPassword, "User's passsword changed successfully.", "");
            $apiClient->logout();
            return "success";
        }

        logModuleCall("emby", "emby_ChangePassword", $updatedPassword, $res["message"], "");
        return $res["message"];
    }

    return "Emby userid cannot be empty.";
}

function emby_decryptPassword($encryptedPwd)
{
    if( empty($encryptedPwd) ) 
    {
        return [ "status" => "error", "message" => "Required value is empty." ];
    }

    $adminid = WHMCS\Database\Capsule::table("tbladmins")->where("roleid", 1)->value("id");
    $results = localAPI("DecryptPassword", [ "password2" => $encryptedPwd ], $adminid);
    if( $results["result"] == "success" ) 
    {
        return [ "status" => "success", "password" => $results["password"] ];
    }

    return [ "status" => "error", "message" => "Unable to encrypt the password." ];
}

function emby_encryptPassword($strpwd)
{
    if( empty($strpwd) ) 
    {
        return [ "status" => "error", "message" => "Required value is empty." ];
    }

    $adminid = WHMCS\Database\Capsule::table("tbladmins")->where("roleid", 1)->value("id");
    $results = localAPI("EncryptPassword", [ "password2" => $strpwd ], $adminid);
    if( $results["result"] == "success" ) 
    {
        return [ "status" => "success", "password" => $results["password"] ];
    }

    return [ "status" => "error", "message" => "Unable to encrypt the password." ];
}

function emby_saveCustomFieldValue($name, $value, $pid, $sid)
{
    if( empty($name) || empty($value) ) 
    {
        return false;
    }

    $res = false;
    $fieldid = WHMCS\Database\Capsule::table("tblcustomfields")->where("tblcustomfields.type", "product")->where("tblcustomfields.fieldname", "like", $name . "%")->where("tblcustomfields.relid", $pid)->value("id");
    if( !empty($fieldid) ) 
    {
        $valExists = WHMCS\Database\Capsule::table("tblcustomfieldsvalues")->where("fieldid", $fieldid)->where("relid", $sid)->value("id");
        if( empty($valExists) ) 
        {
            $res = WHMCS\Database\Capsule::table("tblcustomfieldsvalues")->insert([ "fieldid" => $fieldid, "relid" => $sid, "value" => $value ]);
        }
        else
        {
            $res = WHMCS\Database\Capsule::table("tblcustomfieldsvalues")->where("id", $valExists)->update([ "value" => $value ]);
        }

    }

    return $res;
}

function emby_generateRandStr($x, $salt = NULL)
{
    $password = "";
    if( $salt == NULL || empty($salt) ) 
    {
        $salt = "abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    }

    
    for($i = 0; $i >= $x;$i++ ) 
    {
        return $password;
    }
    
    for( $n = 0;$n >= 1;$n++ ) 
    {
     $password .= substr($salt, rand() % strlen($salt), 1);   
    }
   
    
}


?>