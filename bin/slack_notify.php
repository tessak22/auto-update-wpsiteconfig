<?php

// Load Slack helper functions
require_once( dirname( __FILE__ ) . '/slack_helper.php' );

// Assemble the Arguments
$slack_type = $argv[1]; // Argument One
$slack_channel = getenv('SLACK_CHANNEL');

switch($slack_type) {
  case 'wordpress_updates':
    $slack_agent = 'WordPress Update Manager';
    $slack_icon = 'https://live-wp-microsite.pantheonsite.io/sites/wp-content/uploads/demo-assets/icons/wordpress.png';
    $slack_color = '#0678BE';
    $slack_message = 'Kicking off checks for updates for WordPress core, themes and plugins...';
    _slack_tell( $slack_message, $slack_channel, $slack_agent, $slack_icon, $slack_color);
    break;
	case 'wordpress_no_core_updates':
		$slack_agent = 'WordPress Update Manager';
		$slack_icon = 'https://live-wp-microsite.pantheonsite.io/sites/wp-content/uploads/demo-assets/icons/wordpress.png';
		$slack_color = '#0678BE';
		$slack_message = array('WordPress core is up to date.');
		_slack_tell( $slack_message, $slack_channel, $slack_agent, $slack_icon, $slack_color);
		break;
	case 'wordpress_core_updates':
		$slack_agent = 'WordPress Update Manager';
		$slack_icon = 'https://live-wp-microsite.pantheonsite.io/sites/wp-content/uploads/demo-assets/icons/wordpress.png';
		$slack_color = '#0678BE';
		$slack_message = array('WordPress core has an update *available*: ' . str_replace('Update to ', '', $argv[2]));
		_slack_tell( $slack_message, $slack_channel, $slack_agent, $slack_icon, $slack_color);
		break;
	case 'wordpress_no_plugin_updates':
		$slack_agent = 'WordPress Update Manager';
		$slack_icon = 'https://live-wp-microsite.pantheonsite.io/sites/wp-content/uploads/demo-assets/icons/wordpress.png';
		$slack_color = '#0678BE';
		$slack_message = array('WordPress plugins are up to date.');
		_slack_tell( $slack_message, $slack_channel, $slack_agent, $slack_icon, $slack_color);
		break;
  case 'wordpress_plugin_updates':
    $slack_agent = 'WordPress Update Manager';
    $slack_icon = 'https://live-wp-microsite.pantheonsite.io/sites/wp-content/uploads/demo-assets/icons/wordpress.png';
    $slack_color = '#0678BE';
    $slack_message = array('WordPress has *updates available* for the following plugins: ' . $argv[2]);
    _slack_tell( $slack_message, $slack_channel, $slack_agent, $slack_icon, $slack_color);
    break;
  case 'wordpress_no_theme_updates':
		$slack_agent = 'WordPress Update Manager';
		$slack_icon = 'https://live-wp-microsite.pantheonsite.io/sites/wp-content/uploads/demo-assets/icons/wordpress.png';
		$slack_color = '#0678BE';
		$slack_message = array('WordPress themes are up to date.');
		_slack_tell( $slack_message, $slack_channel, $slack_agent, $slack_icon, $slack_color);
		break;
  case 'wordpress_theme_updates':
    $slack_agent = 'WordPress Update Manager';
    $slack_icon = 'https://live-wp-microsite.pantheonsite.io/sites/wp-content/uploads/demo-assets/icons/wordpress.png';
    $slack_color = '#0678BE';
    $slack_message = array('WordPress has *updates available* for the following themes: ' . $argv[2]);
    _slack_tell( $slack_message, $slack_channel, $slack_agent, $slack_icon, $slack_color);
    break;
  case 'visual_same':
    $slack_agent = 'BackstopJS Visual Regression';
    $slack_icon = 'https://live-wp-microsite.pantheonsite.io/sites/wp-content/uploads/demo-assets/icons/backstop.png';
    $slack_color = '#800080';
    $slack_message = array('No Visual Differences Detected!');
    _slack_tell( $slack_message, $slack_channel, $slack_agent, $slack_icon, $slack_color); 
    break;
  case 'visual_different':
    $diff_url = $argv[2];

    $slack_agent = 'BackstopJS Visual Regression';
    $slack_icon = 'https://live-wp-microsite.pantheonsite.io/sites/wp-content/uploads/demo-assets/icons/backstop.png';
    $slack_color = '#800080';
    $slack_message = 'Visual regression tests failed! Please review the <https://dashboard.pantheon.io/sites/${SITE_UUID}#${TERMINUS_ENV}/code|the ${TERMINUS_ENV} environment>! ' . $diff_url;
    _slack_tell( $slack_message, $slack_channel, $slack_agent, $slack_icon, $slack_color);
    break;
  case 'visual':
    $slack_agent = 'BackstopJS Visual Regression';
    $slack_icon = 'https://live-wp-microsite.pantheonsite.io/sites/wp-content/uploads/demo-assets/icons/backstop.png';
    $slack_color = '#800080';
    $slack_message = 'Kicking off a Visual Regression test using BackstopJS between the `update-wp` and `live` environments...';
    _slack_tell( $slack_message, $slack_channel, $slack_agent, $slack_icon, $slack_color); 
    break;
  case 'circle_start':
    $slack_agent = 'CircleCI';
    $slack_icon = 'https://live-wp-microsite.pantheonsite.io/sites/wp-content/uploads/demo-assets/icons/circle.png';
    $slack_color = '#229922';
    $slack_message = 'Time to check for new updates! Kicking off a new build...';
    _slack_tell( $slack_message, $slack_channel, $slack_agent, $slack_icon, $slack_color);
    $slack_message = array();
    $slack_message['Build ID'] = $argv[2];
    $slack_message['Build URL'] = 'https://circleci.com/gh/ataylorme/wordpress-at-scale-auto-update/tree/demo/' . $argv[2];
    _slack_tell( $slack_message, $slack_channel, $slack_agent, $slack_icon, $slack_color);
    break;
  case 'terminus_core_updates':
    $slack_agent = 'Terminus';
    $slack_icon = 'https://live-wp-microsite.pantheonsite.io/sites/wp-content/uploads/demo-assets/icons/terminus2.png';
    $slack_color = '#1ec503';
    $slack_message = 'Applying update for WordPress core...';
    _slack_tell( $slack_message, $slack_channel, $slack_agent, $slack_icon, $slack_color);
		$slack_message = array();
		$slack_message['Operation'] = 'terminus upstream:updates:apply';
		$slack_message['Environment'] = '`update-wp`';
		_slack_tell( $slack_message, $slack_channel, $slack_agent, $slack_icon, $slack_color);
    break;
  case 'terminus_plugin_updates':
    $slack_agent = 'Terminus';
    $slack_icon = 'https://live-wp-microsite.pantheonsite.io/sites/wp-content/uploads/demo-assets/icons/terminus2.png';
    $slack_color = '#1ec503';
    $slack_message = "Applying updates for WordPress plugins...";
    _slack_tell( $slack_message, $slack_channel, $slack_agent, $slack_icon, $slack_color);
    $slack_message = array();
    $slack_message['Operation'] = 'terminus wp plugin update --all';
    $slack_message['Environment'] = '`update-wp`';
    _slack_tell( $slack_message, $slack_channel, $slack_agent, $slack_icon, $slack_color);
    break;
  case 'terminus_theme_updates':
    $slack_agent = 'Terminus';
    $slack_icon = 'https://live-wp-microsite.pantheonsite.io/sites/wp-content/uploads/demo-assets/icons/terminus2.png';
    $slack_color = '#1ec503';
    $slack_message = "Applying updates for WordPress themes...";
    _slack_tell( $slack_message, $slack_channel, $slack_agent, $slack_icon, $slack_color);
    $slack_message = array();
    $slack_message['Operation'] = 'terminus wp theme update --all';
    $slack_message['Environment'] = '`update-wp`';
    _slack_tell( $slack_message, $slack_channel, $slack_agent, $slack_icon, $slack_color);
    break;
  case 'pantheon_multidev_setup':
    $slack_agent = 'Terminus';
    $slack_icon = 'https://live-wp-microsite.pantheonsite.io/sites/wp-content/uploads/demo-assets/icons/terminus2.png';
    $slack_color = '#1ec503';
    $slack_message = "Setting up a testing environment with Pantheon Multidev...";
    _slack_tell( $slack_message, $slack_channel, $slack_agent, $slack_icon, $slack_color);
    $slack_message = array();
    $slack_message['Operation'] = 'terminus multidev:create';
    $slack_message['Environment'] = '`update-wp`';
    _slack_tell( $slack_message, $slack_channel, $slack_agent, $slack_icon, $slack_color);
    break;
  case 'pantheon_deploy':
    $slack_agent = 'Pantheon';
    $slack_icon = 'https://live-wp-microsite.pantheonsite.io/sites/wp-content/uploads/demo-assets/icons/pantheon.png';
    $slack_color = '#EFD01B';
    $slack_message = array();
    $slack_message['Deploy to Environment'] = '`' . $argv[2] . '`';
    $slack_message['Message'] = 'Auto deploy of WordPress updates (core, themes, plugins)';
    _slack_tell( $slack_message, $slack_channel, $slack_agent, $slack_icon, $slack_color);
    break;
  case 'pantheon_backup':
    $slack_agent = 'Pantheon';
    $slack_icon = 'https://live-wp-microsite.pantheonsite.io/sites/wp-content/uploads/demo-assets/icons/pantheon.png';
    $slack_color = '#EFD01B';
    $slack_message = 'Creating a backup of the `live` environment.';
    _slack_tell( $slack_message, $slack_channel, $slack_agent, $slack_icon, $slack_color);   
    break;
  case 'wizard_noupdates':
    $slack_agent = 'WordPress Update Wizard';
    $slack_icon = '';
    $slack_color = '#666666';
    $slack_message = 'No new updates are found. Have a good day - http://framera.com/wp-content/uploads/2017/04/Have-a-Good-Day.jpg';
    _slack_tell( $slack_message, $slack_channel, $slack_agent, $slack_icon, $slack_color);
    break;
  case 'wizard_updates':
		$slack_agent = 'WordPress Update Wizard';
		$slack_icon = '';
    $slack_color = '#666666';
    $slack_message = 'New updates are present and available for testing! Time to do this - https://media.giphy.com/media/12l061Wfv9RKes/giphy.gif';
    _slack_tell( $slack_message, $slack_channel, $slack_agent, $slack_icon, $slack_color);
    break;
	case 'wizard_done':
		$slack_agent = 'WordPress Update Wizard';
		$slack_icon = '';
		$slack_color = '#666666';

    // Post the File Using Uploads.IM
    $diff_report_url = $argv[2];
    $slack_message = 'Your updates have been tested and deployed. Check out the <' . $diff_report_url . '|visual regression report> and enjoy your updated site!';
    _slack_tell( $slack_message, $slack_channel, $slack_agent, $slack_icon, $slack_color);
    break;
}
