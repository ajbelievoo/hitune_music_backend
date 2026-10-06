<?php

if (!defined("root")) die;

class chapar_push extends bof_type_class
{

  public function exe($args)
  {

    $target_push_ids = [];
    $message_title = null;
    $message_content = null;
    $message_image = null;
    extract($args);

    $vapid_notifications = $fcm_notifications = $apn_notifications = [];

    foreach ($target_push_ids as $target_push_id) {

      if ($target_push_id["platform"] == "web") {

        require_once(chapar_plugin_root . "/third/minishlink_web-push_v7.0.0/autoload.php");

        $vapid_notifications[] = array(
          "subscription" => Minishlink\WebPush\Subscription::create($target_push_id["data"]),
          "payload" => json_encode(array(
            "title" => $message_title,
            "content" => $message_content,
            "image" => $message_image
          ))
        );
      } else {

        if ( $target_push_id["platform"] == "ios" && is_string( $target_push_id["data"] ) && preg_match( "/^[A-Fa-f0-9]{64}$/", $target_push_id["data"] ) )
        $apn_notifications[] = $target_push_id["data"];
        else
        $fcm_notifications[] = $target_push_id["data"];
      }
    }

    if (!empty($vapid_notifications)) {
      bof()->chapar_push->exe_vapid($vapid_notifications);
    }

    if (!empty($fcm_notifications)) {
      bof()->chapar_push->exe_fcm($fcm_notifications, $args);
    }

    if (!empty($apn_notifications)) {
      bof()->chapar_push->exe_apn($apn_notifications, $args);
    }
  }
  
  public function exe_vapid($notifications)
  {

    $webPush = new Minishlink\WebPush\WebPush([
      'VAPID' => [
        'subject' => web_address,
        'publicKey' => vapid_public,
        'privateKey' => vapid_private,
      ],
    ]);

    foreach ($notifications as $notification) {
      $webPush->queueNotification(
        $notification['subscription'],
        $notification['payload']
      );
    }

    foreach ($webPush->flush() as $report) {
      $endpoint = $report->getRequest()->getUri()->__toString();
      if ($report->isSuccess()) {
      } else {
      }
    }
  }

  public function exe_fcm($notifications, $args)
  {

    require_once(bof_root . "/app/core/third/googleapi/vendor/autoload.php");

    $message_title = null;
    $message_content = null;
    $message_image = null;
    $message_link = null;
    $extra = [];
    extract($args);

    $serviceAccountPath = root . "app/fcm-service-account.json";
    $projectId = bof()->object->db_setting->get("fcm_project_number") ?: bof()->object->db_setting->get("fcm_project_id");

    if (!file_exists($serviceAccountPath) || !$projectId) {
      return;
    }

    // Get access token
    $client = new Google\Client();
    $client->setAuthConfig($serviceAccountPath);
    $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
    $accessToken = $client->fetchAccessTokenWithAssertion()['access_token'];

    foreach ($notifications as $token) {

      $payload = [
        'message' => [
          'token' => $token,
          'notification' => [
            'title' => $message_title,
            'body' => $message_content,
            'image' => $message_image
          ],
          'data' => [
            'title' => (string) $message_title,
            'content' => (string) $message_content,
            'image' => (string) $message_image,
            'link' => (string) $message_link
          ],
          'android' => [
            'notification' => [
              'image' => $message_image,
              'channel_id' => 'hitune_notifications',
              'sound' => 'default',
              'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
            ]
          ]
        ]
      ];

      // Send not
      $res = bof()->curl->exe([
        "url" => "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
        "posts" => json_encode($payload),
        "json" => true,
        "headers" => [
          'Authorization: Bearer ' . $accessToken,
        ],
        "cache" => false,
        "cache_save" => false
      ]);

      // Purge dead tokens so they stop being retried
      $resBody = is_array($res) ? ( $res["body"] ?? "" ) : (string) $res;
      if ( strpos( $resBody, "UNREGISTERED" ) !== false || strpos( $resBody, "NotRegistered" ) !== false ){
        bof()->db->_delete(array(
          "table" => "_u_push_subs",
          "where" => array(
            [ "data", "=", json_encode( $token ) ]
          )
        ));
      }

    }

  }

  public function exe_apn($tokens, $args)
  {

    $message_title = null;
    $message_content = null;
    $message_image = null;
    extract($args);

    // Verify APN configuration
    $teamId = bof()->object->db_setting->get("apn_team_id");
    $keyId = bof()->object->db_setting->get("apn_key_id");
    $bundleId = bof()->object->db_setting->get("apn_bundle_id");
    $p8File = root . "/app/apn-AuthKey.p8";

    $environment = bof()->object->db_setting->get("apn_environment") ?: 'production';

    if (!$teamId || !$keyId || !$bundleId || !file_exists($p8File)) {
      return;
    }

    // Include ApnsPHP library
    require_once chapar_plugin_root . '/third/apns-php/vendor/autoload.php';

    try {

      // Initialize push
      $client = new \ApnsPHP\Push(
        $environment === 'sandbox' ?
          \ApnsPHP\Push::ENVIRONMENT_SANDBOX :
          \ApnsPHP\Push::ENVIRONMENT_PRODUCTION,
        $p8File
      );

      // Set provider credentials
      $client->setProviderToken($this->generateAPNToken($teamId, $keyId, $p8File));
      $client->setTopic($bundleId);
      $client->connect();

      foreach ($tokens as $token) {
        $message = new \ApnsPHP\Message($token);
        $message->setCustomIdentifier("msg_" . time());
        $message->setText($message_content);
        $message->setTitle($message_title);

        if ($message_image) {
          $message->setCustomProperty('image', $message_image);
        }

        $message->setSound();
        $client->add($message);
      }

      // Send all messages and disconnect
      $client->send();
      $client->disconnect();

      // Handle errors if needed
      $errors = $client->getErrors();
      if (!empty($errors)) {
        // Log or handle errors
      }

    } catch (\Exception $e) {
      // Handle exception
    }
  }

  private function generateAPNToken($teamId, $keyId, $p8File)
  {
    // Same JWT generation as before
    $header = base64_encode(json_encode([
      'alg' => 'ES256',
      'kid' => $keyId
    ]));

    $payload = base64_encode(json_encode([
      'iss' => $teamId,
      'iat' => time()
    ]));

    $signingInput = $header . '.' . $payload;
    $signature = '';
    $privateKey = openssl_pkey_get_private(file_get_contents($p8File));

    openssl_sign($signingInput, $signature, $privateKey, 'sha256');

    return $signingInput . '.' . base64_encode($signature);
  }

}
