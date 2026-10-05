<?php

require_once( dirname(__FILE__) . "/env_loader.php" );

define( "purchase_code", bof_env("PURCHASE_CODE", "") );
define( "sign_code", bof_env("SIGN_CODE", "") );
define( "owner", bof_env("OWNER", "") );

define( "sign_key", bof_env("SIGN_KEY", "") );
define( "vapid_public", bof_env("VAPID_PUBLIC", "") );
define( "vapid_private", bof_env("VAPID_PRIVATE", "") );
?>
