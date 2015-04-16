<?php
/*
  Plugin Name: Wall.ly Social Media Plugin
  Plugin URI: http://pixels.fi
  Description: Social Media Wall
  Version: 1.0
  Author: Nur Sah Ketene
*/

require_once('lib/twitter-api-php-master/TwitterAPIExchange.php');

function wallly($search_criteria){
  $twitter_settings = array(
    'oauth_access_token' => get_option('twitter_access_token'),
    'oauth_access_token_secret' => get_option('twitter_access_token_secret'),
    'consumer_key' => get_option('twitter_consumer_key'),
    'consumer_secret' => get_option('twitter_consumer_secret')
    );

  $instagram_settings = array(
    'apiKey'      => get_option('instagram_api_key'),
    'apiSecret'   => get_option('instagram_api_secret'),
    'apiCallback' => get_option('instagram_api_callback')
    );

  if ( !isset($instagram_settings['apiKey']) && !isset($twitter_settings['consumer_key']) ) {
    die("You need some social creds. Talk to your admin and set the credentials from Wall.ly Settings Page!!!");
  }

  $cached_feeds = get_transient( 'cached_feeds' );

  $cached_feeds = false;

  if ( false === ( $cached_feeds ) ) {

    $response = "";
    if ( isset($instagram_settings['apiKey']) || !sset($instagram_settings['apiSecret']) ){
      
      $instagram = wal_loadInstagram($instagram_settings, $search_criteria);  
      $response = $instagram;
    }
    
    if( isset($twitter_settings['oauth_access_token']) || isset($twitter_settings['oauth_access_token_secret']) || isset($twitter_settings['consumer_key']) ) {

      $twitter_by_user = wal_loadTweetsByUserName($twitter_settings, $search_criteria);

      $twitter_by_hash_tag = wal_loadTweetsByHashTag($twitter_settings, $search_criteria);

      $response = $response + $twitter_by_user + $twitter_by_hash_tag;
    }

    $response = json_encode($response);

    set_transient('cached_feeds', $response, 60*2 );
  }


  $cached_feeds = get_transient('cached_feeds');

  return json_decode($cached_feeds);

}

add_action( 'wp_ajax_wallly', 'wallly' );
add_action( 'wp_ajax_nopriv_wallly', 'wallly' );

function add_wallly_style(){
  wp_enqueue_style( "wallly", plugin_dir_url(__FILE__) . "stylesheet/wallly_style.css" );
}

add_action( "wp_enqueue_scripts", "add_wallly_style" );  


function wal_loadTweetsByUserName($twitter_settings, $search_criteria){
  $twitter_url = "https://api.twitter.com/1.1/statuses/home_timeline.json";
  $twitter_options = "?screen_name=" . $search_criteria['twitter_user_name'] . "&count=" . $search_criteria['twitter_count'] . "&include_entities=true";
  $twitter = new TwitterAPIExchange($twitter_settings);
  $response = $twitter->setGetfield($twitter_options)
  ->buildOauth($twitter_url, "GET")
  ->performRequest(); 

  $response = json_decode($response);

  $formatted_response = array();
  foreach($response as $tweet){
    $formatted_response[$tweet->id] = array(
      'created_at' => strtotime($tweet->created_at),
      'id' => $tweet->id,
      'username' => $tweet->user->name,
      'handle' => $tweet->user->screen_name,
      'content' => $tweet->text,
      'source' => 'twitter'
      );

    $formatted_response[$tweet->id]['media_url'] = isset($tweet->entities->media) ? $tweet->entities->media[0]->media_url : NULL;
  }

  return $formatted_response;
}

function wal_loadTweetsByHashTag($twitter_settings, $search_criteria){

  $twitter_url = "https://api.twitter.com/1.1/search/tweets.json";
  $twitter_options = "?q=#" . $search_criteria['hash_tag'] . "&count=" . $search_criteria['twitter_count'] . "&include_entities=true";
  $twitter = new TwitterAPIExchange($twitter_settings);
  $response = $twitter->setGetfield($twitter_options)
  ->buildOauth($twitter_url, "GET")
  ->performRequest(); 

  $response = json_decode($response);

  $formatted_response = array();
  foreach($response->statuses as $tweet){
    $formatted_response[$tweet->id] = array(
      'created_at' => strtotime($tweet->created_at),
      'id' => $tweet->id,
      'username' => $tweet->user->name,
      'handle' => $tweet->user->screen_name,
      'content' => $tweet->text,
      'source' => 'twitter'
      );

    $formatted_response[$tweet->id]['media_url'] = isset($tweet->entities->media) ? $tweet->entities->media[0]->media_url : NULL;
    
  }

  return $formatted_response;

}

function wal_loadInstagram($instagram_settings, $search_criteria){
  $user_id = isset($search_criteria['instagram_user_id']) ? $search_criteria['instagram_user_id'] : false;
  $hash_tag = $search_criteria['hash_tag'];
  $client_id = $instagram_settings['apiKey'];
  $count = $search_criteria['instagram_count'];
  $response = array();
  $user_search = array();
  $hash_tag_search = array();

  if ( $user_id ){
    $url = 'https://api.instagram.com/v1/users/'. $user_id .'/media/recent?client_id=' . $client_id . '&count=' . 3;
    $user_search = wp_remote_retrieve_body( wp_remote_get( $url ) );
    $user_search = json_decode($user_search);
  }

  if ( $hash_tag ){
    $url = 'https://api.instagram.com/v1/tags/' . $hash_tag . '/media/recent?client_id=' . $client_id . '&count=' . $count;
    $hash_tag_search = wp_remote_retrieve_body( wp_remote_get( $url ) );
    $hash_tag_search = json_decode($hash_tag_search);
  }

  $formatted_response = array();

  if ( $user_search ){
    foreach($user_search->data as $gram){
      $formatted_response["instagram_" . $gram->caption->id] = array(
        'created_at' => $gram->caption->created_time,
        'id' => $gram->caption->id,
        'username' => $gram->user->full_name,
        'handle' => $gram->user->username,
        'content' => $gram->caption->text,
        'media_url' => $gram->images->standard_resolution->url,
        'source' => 'instagram'
        );
    }
  }

  if ($hash_tag_search){
   foreach($hash_tag_search->data as $gram){
    $formatted_response[$gram->caption->id] = array(
      'created_at' => $gram->caption->created_time,
      'id' => $gram->caption->id,
      'username' => $gram->user->full_name,
      'handle' => $gram->user->username,
      'content' => $gram->caption->text,
      'media_url' => $gram->images->standard_resolution->url,
      'source' => 'instagram'
      );
    }   
  }

  return $formatted_response;
}


add_action('admin_menu', 'wallly_plugin_settings');

// Wallly Admin Settings

function register_mysettings() { // whitelist options
  register_setting( 'twitter-group', 'twitter_consumer_key' );
  register_setting( 'twitter-group', 'twitter_consumer_secret' );
  register_setting( 'twitter-group', 'twitter_access_token' );
  register_setting( 'twitter-group', 'twitter_access_token_secret' );
  register_setting( 'instagram-group', 'instagram_api_key' );
  register_setting( 'instagram-group', 'instagram_api_secret' );
  register_setting( 'instagram-group', 'instagram_api_callback' );
}

function wallly_plugin_settings() {
  add_menu_page('Wallly Settings', 'Wallly Settings', 'administrator', 'wallly_settings', 'wallly_admin_display_settings');
  if ( is_admin() ){ // admin actions
    add_action( 'admin_init', 'register_mysettings' );
  } else {
  // non-admin enqueues, actions, and filters
  }
}

function wallly_admin_display_settings(){
?>
<div>
  <h2>
    Welcome to Wallly, The Friendly Social Media Wall.
  </h2>
  <p>
    Here you can save the API KEYS for the feeds that you would like to show.
  </p>
</div>
<div>
  <form method='post' action='options.php'>
    <?php settings_fields( 'twitter-group' ); ?>
    <?php do_settings_sections( 'twitter-group' ); ?>
    <div>
      <h2>
        Twitter
      </h2>
      <table class="twitter_table">
        <tr>
          <td>
            Consumer Key:
          </td>
          <td>
            <input type="text" name="twitter_consumer_key" value="<?php echo esc_attr( get_option('twitter_consumer_key') );?>">
          </td>
        </tr>
        <tr>
          <td>
            Consumer Secret:
          </td>
          <td>
            <input type="text" name="twitter_consumer_secret" value="<?php echo esc_attr( get_option('twitter_consumer_secret') );?>">
          </td>
        </tr>
        <tr>
          <td>
            Access Token:
          </td>
          <td>
            <input type="text" name="twitter_access_token" value="<?php echo esc_attr( get_option('twitter_access_token') );?>">
          </td>
        </tr>
        <tr>
          <td>
            Access Token Secret:
          </td>
          <td>
            <input type="text" name="twitter_access_token_secret" value="<?php echo esc_attr( get_option('twitter_access_token_secret') );?>">
          </td>
        </tr>

      </table>
    </div>
    <?php submit_button()?>
  </form>
</div>

<div>
  <form method='post' action='options.php'>
    <?php settings_fields( 'instagram-group' ); ?>
    <?php do_settings_sections( 'instagram-group' ); ?>
    <div>
      <h2>
        Instagram
      </h2>
      <table class="instagram_table">
        <tr>
          <td>
            Api Key:
          </td>
          <td>
            <input type="text" name="instagram_api_key" value="<?php echo esc_attr( get_option('instagram_api_key') );?>">
          </td>
        </tr>
        <tr>
          <td>
            Api Secret:
          </td>
          <td>
            <input type="text" name="instagram_api_secret" value="<?php echo esc_attr( get_option('instagram_api_secret') );?>">
          </td>
        </tr>
        <tr>
          <td>
            Api CallBack:
          </td>
          <td>
            <input type="text" name="instagram_api_callback" value="<?php echo esc_attr( get_option('instagram_api_callback') );?>">
          </td>
        </tr>
      </table>
    </div>
    <?php submit_button()?>
  </form>
</div>
<?php
wp_register_script('wallly-script', plugin_dir_url(__FILE__) . 'js/wallly-script.js',array('jquery'));
wp_enqueue_script('wallly-script');
}

?>