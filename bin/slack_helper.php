<?php

/**
 * Helper Function to Alert Slack
 */
function _slack_tell( $message, $slack_channel_name, $slack_user_name, $slack_icon_url, $left_color_bar = '#EFD01B' ) {

  if (is_array($message)) {
    $fields = array();
    foreach ($message as $title => $text) {
      if ($title) {
        $fields[] = array(
          'title' => $title,
          'value' => $text,
          'short' => 'true',
        );
      } else {
        $fields[] = array(
          'value' => $text,
          'short' => 'true',
        );
      }
    }
    $attachment = array(
      'fallback' => $message,
      'color'    => $left_color_bar, 
      'fields'   => $fields,
      'mrkdwn_in' => ["text", "pretext", "fields"]
    );
  }
  else {
    $fields = array(
      array(
        'value' => $message,
        'short' => 'false',
      ),
    );    	
    $attachment = FALSE;
  }
  _slack_notification( $slack_channel_name, $slack_user_name, $message, $slack_icon_url, $attachment );
}

/**
 * Send a notification to slack
 */
function _slack_notification( $channel, $username, $text, $icon_url, $attachment = false ) {
  $defaults = array();
	$circle_branch = getenv('CIRCLE_BRANCH');
  $slack_url = ('demo' === $circle_branch) ? getenv('SLACK_HOOK_DEMO_URL') : getenv('SLACK_HOOK_URL');

	$post = array(
		'username' => $username,
		'channel'  => $channel,
		'icon_url' => $icon_url,
		'mrkdwn' => true,
	);

	if ( $attachment !== false && is_array( $attachment ) ) {
		$post['attachments'] = array( $attachment );
	} else {
		$post['text'] = $text;
	}

	$payload = json_encode( $post );
	$ch      = curl_init();
	curl_setopt( $ch, CURLOPT_URL, $slack_url );
	curl_setopt( $ch, CURLOPT_POST, 1 );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $ch, CURLOPT_TIMEOUT, 5 );
	curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json' ) );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );

	$result = curl_exec( $ch );
	curl_close( $ch );
}
