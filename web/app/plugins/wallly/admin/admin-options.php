<?php

// Settings aren't registered?? not sure this is the way to go...
// 
add_action( 'admin_menu', 'wallly_add_admin_menu' );
add_action( 'admin_init', 'wallly_settings_init' );


function wallly_add_admin_menu() {
  add_options_page( 'Wallly', 'Wallly', 'manage_options', 'wallly', 'wallly_options_page' );
}


function wallly_settings_init(  ) { 

  register_setting( 'walllySettingsPage', 'wallly_settings' );

  add_settings_section(
    'wallly_general_section',
    __('General Settings', 'wallly'),
    'wallly_general_section_render',
    'walllySettingsPage'
  );

  add_settings_field(
    'wallly_max_results', 
    __( 'Maximum Results', 'wallly' ), 
    'wallly_max_results_render', 
    'walllySettingsPage', 
    'wallly_general_section',
    array( 'label_for' => 'wallly_max_results' )
  );

  add_settings_field( 
    'wallly_autorefresh', 
    __( 'Automatic refresh', 'wallly' ), 
    'wallly_autorefresh_render', 
    'walllySettingsPage', 
    'wallly_general_section'
  );

  add_settings_field(
    'wallly_refresh_rate',
    __( 'Refresh Rate', 'wallly' ),
    'wallly_refresh_rate_render',
    'walllySettingsPage',
    'wallly_general_section', 
    array( 'label_for' => 'wallly_refresh_rate' )
  );

  add_settings_section(
    'wallly_search_criteria_section',
    __('Default Search Criteria', 'wallly'),
    'wallly_search_criteria_section_render',
    'walllySettingsPage'
  );

  add_settings_field( 
    'wallly_twitter_user', 
    __( 'Twitter User', 'wallly' ), 
    'wallly_twitter_user_render', 
    'walllySettingsPage', 
    'wallly_search_criteria_section',
    array( 'label_for' => 'wallly_twitter_user' )
  );

  add_settings_field( 
    'wallly_instagram_user', 
    __( 'Instagram User', 'wallly' ), 
    'wallly_instagram_user_render', 
    'walllySettingsPage', 
    'wallly_search_criteria_section',
    array( 'label_for' => 'wallly_instagram_user' )
  );

  add_settings_field( 
    'wallly_hashtags', 
    __( 'Hashtags', 'wallly' ), 
    'wallly_hashtags_render', 
    'walllySettingsPage', 
    'wallly_search_criteria_section',
    array( 'label_for' => 'wallly_hashtags' )
  );

  add_settings_field( 
    'wallly_twitter_user', 
    __( 'Twitter User', 'wallly' ), 
    'wallly_twitter_user_render', 
    'walllySettingsPage', 
    'wallly_search_criteria_section',
    array( 'label_for' => 'wallly_twitter_user' )
  );


  add_settings_section(
    'wallly_twitter_section', 
    __( 'Twitter Settings', 'wallly' ), 
    'wallly_twitter_section_render', 
    'walllySettingsPage'
  );

  add_settings_field( 
    'wallly_twitter_hide_retweets', 
    __( 'Hide Retweets', 'wallly' ), 
    'wallly_twitter_hide_retweets_render', 
    'walllySettingsPage', 
    'wallly_twitter_section'
  );

  add_settings_field( 
    'wallly_twitter_key', 
    __( 'Consumer Key (API Key)', 'wallly' ), 
    'wallly_twitter_key_render', 
    'walllySettingsPage', 
    'wallly_twitter_section',
    array( 'label_for' => 'wallly_twitter_key' )
  );

  add_settings_field( 
    'wallly_twitter_secret', 
    __( 'Consumer Secret (API Secret)', 'wallly' ), 
    'wallly_twitter_secret_render', 
    'walllySettingsPage', 
    'wallly_twitter_section',
    array( 'label_for' => 'wallly_twitter_secret' )
  );

  add_settings_field( 
    'wallly_twitter_token', 
    __( 'Access Token', 'wallly' ), 
    'wallly_twitter_token_render', 
    'walllySettingsPage', 
    'wallly_twitter_section',
    array( 'label_for' => 'wallly_twitter_token' )
  );

  add_settings_field( 
    'wallly_twitter_token_secret', 
    __( 'Access Token Secret', 'wallly' ), 
    'wallly_twitter_token_secret_render', 
    'walllySettingsPage', 
    'wallly_twitter_section',
    array( 'label_for' => 'wallly_twitter_token_secret' )
  );

  add_settings_section(
    'wallly_instagram_section', 
    __( 'Instagram Settings', 'wallly' ), 
    'wallly_instagram_section_render', 
    'walllySettingsPage'
  );

  add_settings_field( 
    'wallly_instagram_client_id', 
    __( 'Client ID', 'wallly' ), 
    'wallly_instagram_client_id_render', 
    'walllySettingsPage', 
    'wallly_instagram_section',
    array( 'label_for' => 'wallly_instagram_client_id' )
  );

  add_settings_field( 
    'wallly_instagram_client_secret', 
    __( 'Client Secret', 'wallly' ), 
    'wallly_instagram_client_secret_render', 
    'walllySettingsPage', 
    'wallly_instagram_section',
    array( 'label_for' => 'wallly_instagram_client_secret' )
  );


}

function wallly_max_results_render(  ) {
  $options = get_option( 'wallly_settings' );
  ?>
  <input type='number' id="wallly_max_results" name='wallly_settings[wallly_max_results]' value='<?php echo isset($options['wallly_max_results']) ? $options['wallly_max_results'] : 20; ?>'>
  <?php
}

function wallly_autorefresh_render() {
  $options = get_option( 'wallly_settings' );
  if (!isset($options['wallly_autorefresh'])) {
    $check_option = false;
  } else {
    $check_option = $options['wallly_autorefresh'];
  }
  ?>
  <label><input type='checkbox' name='wallly_settings[wallly_autorefresh]' value="1" <?php checked(1, $check_option); ?>> <?php _e('Check this box to enable autorefresh.', 'wallly'); ?></label>
  <?php
}

function wallly_refresh_rate_render(  ) {
  $options = get_option( 'wallly_settings' );
  ?>
  <input type='number' id="wallly_refresh_rate" name='wallly_settings[wallly_refresh_rate]' value='<?php echo isset($options['wallly_refresh_rate']) ? $options['wallly_refresh_rate'] : 120; ?>'>
  <?php
}

function wallly_twitter_user_render(  ) {
  $options = get_option( 'wallly_settings' );
  ?>
  <input type='text' id="wallly_twitter_user" name='wallly_settings[wallly_twitter_user]' value='<?php echo isset($options['wallly_twitter_user']) ? $options['wallly_twitter_user'] : ''; ?>'>
  <?php
}

function wallly_instagram_user_render(  ) {
  $options = get_option( 'wallly_settings' );
  ?>
  <input type='text' id="wallly_instagram_user" name='wallly_settings[wallly_instagram_user]' value='<?php echo isset($options['wallly_instagram_user']) ? $options['wallly_instagram_user'] : ''; ?>'>
  <?php
}

function wallly_hashtags_render() {
  $options = get_option( 'wallly_settings' );
  ?>
  <input type='text' id="wallly_hashtags" name='wallly_settings[wallly_hashtags]' value='<?php echo isset($options['wallly_hashtags']) ? $options['wallly_hashtags'] : ''; ?>'>
  <?php
}

function wallly_twitter_hide_retweets_render() {
  $options = get_option( 'wallly_settings' );
  if (!isset($options['wallly_twitter_hide_retweets'])) {
    $check_option = false;
  } else {
    $check_option = $options['wallly_twitter_hide_retweets'];
  }
  ?>
  <label><input type='checkbox' name='wallly_settings[wallly_twitter_hide_retweets]' value="1" <?php checked(1, $check_option); ?>> <?php _e('Check this box to hide retweets.', 'wallly'); ?></label>
  <?php
}

function wallly_twitter_key_render() {
  $options = get_option( 'wallly_settings' );
  ?>
  <input type='text' id="wallly_twitter_key" name='wallly_settings[wallly_twitter_key]' value='<?php echo isset($options['wallly_twitter_key']) ? $options['wallly_twitter_key'] : ''; ?>'>
  <?php
}

function wallly_twitter_secret_render() {
  $options = get_option( 'wallly_settings' );
  ?>
  <input type='text' id="wallly_twitter_secret" name='wallly_settings[wallly_twitter_secret]' value='<?php echo isset($options['wallly_twitter_secret']) ? $options['wallly_twitter_secret'] : ''; ?>'>
  <?php
}

function wallly_twitter_token_render() {
  $options = get_option( 'wallly_settings' );
  ?>
  <input type='text' id="wallly_twitter_token" name='wallly_settings[wallly_twitter_token]' value='<?php echo isset($options['wallly_twitter_token']) ? $options['wallly_twitter_token'] : ''; ?>'>
  <?php
}

function wallly_twitter_token_secret_render() {
  $options = get_option( 'wallly_settings' );
  ?>
  <input type='text' id="wallly_twitter_token_secret" name='wallly_settings[wallly_twitter_token_secret]' value='<?php echo isset($options['wallly_twitter_token_secret']) ? $options['wallly_twitter_token_secret'] : ''; ?>'>
  <?php
}

function wallly_instagram_client_id_render() {
  $options = get_option( 'wallly_settings' );
  ?>
  <input type='text' id="wallly_instagram_client_id" name='wallly_settings[wallly_instagram_client_id]' value='<?php echo isset($options['wallly_instagram_client_id']) ? $options['wallly_instagram_client_id'] : ''; ?>'>
  <?php
}

function wallly_instagram_client_secret_render() {
  $options = get_option( 'wallly_settings' );
  ?>
  <input type='text' id="wallly_instagram_client_secret" name='wallly_settings[wallly_instagram_client_secret]' value='<?php echo isset($options['wallly_instagram_client_secret']) ? $options['wallly_instagram_client_secret'] : ''; ?>'>
  <?php
}


function wallly_search_criteria_section_render() {

}

function wallly_general_section_render() {

}

function wallly_twitter_section_render(  ) { 

  // echo __( 'This section description', 'wallly' );

}

function wallly_instagram_section_render() {

}

function wallly_options_page(  ) {
  ?>

  <div class="wrap">
    <h2><?php _e('Welcome to Wallly, The Friendly Social Media Wall.', 'wallly'); ?></h2>
    <p><?php _e('Set up your API keys and define what you would like to display in the social media wall.', 'wallly'); ?></p>
    <form action='options.php' method='post'>
      
      <?php
      settings_fields( 'walllySettingsPage' );
      do_settings_sections( 'walllySettingsPage' );
      submit_button();
      ?>
      
    </form>
  </div>
  <?php
}

?>