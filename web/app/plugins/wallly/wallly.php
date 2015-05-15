<?php
  /*
    Plugin Name: Wall.ly Social Media Plugin
    Plugin URI: http://pixels.fi
    Description: The Friendly Social Media Wall
    Version: 1.1
    Author: booncon PIXELS
  */

  /* Created by Nur Sah Ketene, Thomas Hurd, Lukas Jakob Hafner */

  if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

  if( !function_exists( 'dpm' ) ) {
    function dpm($var) {
      echo '<pre>' . print_r($var, true) . '</pre>';
    }
  }
  require_once('admin/admin-options.php');

  require_once('lib/twitter-api-php-master/TwitterAPIExchange.php');

  function wallly ($search_criteria, $refresh = false) {
    $wallly_options = get_option( 'wallly_settings' );
    $default_search_criteria = array(
      'hashtags' => $wallly_options['wallly_hashtags'],
      'twitter_user_search' => $wallly_options['wallly_twitter_user'],
      'max_results' => $wallly_options['wallly_max_results'],
      'hide_retweets' => $wallly_options['wallly_twitter_hide_retweets'],
      'instagram_user_search' => $wallly_options['wallly_instagram_user'],
      'refresh_rate' => $wallly_options['wallly_refresh_rate']
    );

    $search_criteria = array_merge($default_search_criteria, $search_criteria);

    $twitter_settings = array(
      'oauth_access_token' => $wallly_options['wallly_twitter_token'],
      'oauth_access_token_secret' => $wallly_options['wallly_twitter_token_secret'],
      'consumer_key' => $wallly_options['wallly_twitter_key'],
      'consumer_secret' => $wallly_options['wallly_twitter_secret']
    );

    $instagram_settings = array(
      'apiKey'      => $wallly_options['wallly_instagram_client_id'],
      'apiSecret'   => $wallly_options['wallly_instagram_client_secret']
    );

    if ( !isset($instagram_settings['apiKey']) && !isset($twitter_settings['consumer_key']) ) {
      die('You need some social creds. Set up the credentials from the Wallly Settings Page!');
    }

    if (!$refresh) {
      $cached_feeds = $_GET['wallly_ignore_cache'] == 'true' ? false : get_transient( 'wallly_cached_feeds' );
    } else {
      $cached_feeds = $_GET['wallly_ignore_cache'] == 'true' ? false : get_transient( 'wallly_cached_refresh_feeds' );
    }

    if ( $cached_feeds === false ) {
      $response = array();

      if ( isset($instagram_settings['apiKey']) && isset($instagram_settings['apiSecret']) ){      
        $instagram_results = wal_loadInstagram($instagram_settings, $search_criteria, $refresh);
        if ($instagram_results != NULL) {
          array_push($response, $instagram_results);
        }  
      }

      if( isset($twitter_settings['oauth_access_token']) && isset($twitter_settings['oauth_access_token_secret']) && isset($twitter_settings['consumer_key']) ) {
        $twitter_results = wal_loadTweets($twitter_settings, $search_criteria, $refresh);
        if ($twitter_results != NULL) {
          array_push($response, $twitter_results);
        }
      }      

      if (count($response) > 0) {
        $splice_offset = ceil($search_criteria['max_results'] / count($response));
        $merged_results = array();

        function cmp_timestamp($a, $b) {
          return $b['created_at'] - $a['created_at'];
        }

        foreach ($response as $social_source) {
          usort($social_source, "cmp_timestamp");
          $social_splice = array_splice($social_source, 0, $splice_offset);
          $merged_results = array_merge($merged_results, $social_splice);
        }      

        $response = array_splice($merged_results, 0, $search_criteria['max_results']);
        usort($response, "cmp_timestamp");

        if (!$refresh) {
          set_transient('wallly_cached_feeds', json_encode( $response ), $search_criteria['refresh_rate'] - 1 );
        } else {
          set_transient('wallly_cached_refresh_feeds', json_encode( $response ), $search_criteria['refresh_rate'] - 1 );
        }
      }
    }

    if (!$refresh) {
      $cached_feeds = get_transient('wallly_cached_feeds');
    } else {
      $cached_feeds = get_transient('wallly_cached_refresh_feeds');
    }    

    return json_decode($cached_feeds);

  }

  add_action( 'wp_ajax_wallly', 'wallly' );
  add_action( 'wp_ajax_nopriv_wallly', 'wallly' );

  function add_wallly_style(){
    wp_enqueue_style( "wallly", plugin_dir_url(__FILE__) . "stylesheet/style.css" );
  }

  add_action( "wp_enqueue_scripts", "add_wallly_style" );  

  function wal_loadTweets($twitter_settings, $search_criteria, $refresh) {
    $user_response = $tag_response = $formatted_response = array();
    if (isset($search_criteria['twitter_user_search']) && $search_criteria['twitter_user_search'] !== '') {
      $twitter_url = "https://api.twitter.com/1.1/statuses/home_timeline.json";
      $twitter_options = "?screen_name=" . $search_criteria['twitter_user_search'] . "&count=" . $search_criteria['max_results'] . "&include_entities=true";
      
      if ($refresh) {
        $twitter_options .= '&since_id=' . get_option('wallly_twitter_offset_user');
      }

      $twitter = new TwitterAPIExchange($twitter_settings);
      $user_response = $twitter->setGetfield($twitter_options)
      ->buildOauth($twitter_url, "GET")
      ->performRequest();
      if (isset(json_decode($user_response)->search_metadata->max_id)) {
        update_option('wallly_twitter_offset_user', json_decode($user_response)->search_metadata->max_id);
      }
      if (isset(json_decode($user_response)->statuses)) {
        $user_response = json_decode($user_response)->statuses;
      }  
    }  

    if (isset($search_criteria['hashtags'])) {
      $twitter_url = "https://api.twitter.com/1.1/search/tweets.json";    
      if ($search_criteria['hide_retweets']) {
        $search_criteria['hashtags'] .= '-filter:retweets';
      }
      $twitter_options = "?q=#" . $search_criteria['hashtags'] . "&count=" . $search_criteria['max_results'] . "&include_entities=true";
      if ($refresh) {
        $twitter_options .= '&since_id=' . get_option('wallly_twitter_offset_tag');
      }
      $twitter = new TwitterAPIExchange($twitter_settings);
      $tag_response = $twitter->setGetfield($twitter_options)
      ->buildOauth($twitter_url, "GET")
      ->performRequest();
      if (isset(json_decode($tag_response)->search_metadata->max_id)) {
        update_option('wallly_twitter_offset_tag', json_decode($tag_response)->search_metadata->max_id);
      }
      if (isset(json_decode($tag_response)->statuses)) {
        $tag_response = json_decode($tag_response)->statuses;
      }  
    }

    $response = array_merge($user_response, $tag_response);

    foreach ($response as $tweet) {
      $formatted_response['twitter_' . $tweet->id] = array(
        'created_at' => strtotime($tweet->created_at),
        'id' => $tweet->id,
        'user' => array(
          'name' => $tweet->user->name,
          'handle' => $tweet->user->screen_name,
          'image' => $tweet->user->profile_image_url_https
        ),
        'content' => $tweet->text,
        'media_url' => isset($tweet->entities->media) ? $tweet->entities->media[0]->media_url : NULL,
        'link' => isset($tweet->entities->urls[0]->expanded_url) ? $tweet->entities->urls[0]->expanded_url : NULL,
        'source' => 'Twitter'
      ); 
    }

    if (count($formatted_response) > 0) {
      return $formatted_response;
    } else {
      return NULL;
    }
  }

  function wal_loadInstagram($instagram_settings, $search_criteria, $refresh) {
    $user_response = $tag_response = $formatted_response = array();
    $user_id = isset($search_criteria['instagram_user_search']) ? $search_criteria['instagram_user_search'] : false;
    $hash_tag = $search_criteria['hashtags'];
    $client_id = $instagram_settings['apiKey'];
    $count = $search_criteria['max_results'];
    $response = array();

    if ( $user_id ) {
      $url = 'https://api.instagram.com/v1/users/'. $user_id .'/media/recent?client_id=' . $client_id . '&count=' . $count;
      if ($refresh) {
        $url .= '&min_tag_id=' . get_option('wallly_instagram_offset_user');
      }
      $user_response = wp_remote_retrieve_body( wp_remote_get( $url ) );
      if (isset(json_decode($user_response)->pagination->min_tag_id)) {
        update_option('wallly_instagram_offset_user', json_decode($user_response)->pagination->min_tag_id , true);
      }
      if (isset(json_decode($user_response)->data)) {
        $user_response = json_decode($user_response)->data;
      }  
    }

    if ( $hash_tag ){
      $url = 'https://api.instagram.com/v1/tags/' . $hash_tag . '/media/recent?client_id=' . $client_id . '&count=' . $count;
      if ($refresh) {
        $url .= '&min_tag_id=' . get_option('wallly_instagram_offset_tag');
      }
      $tag_response = wp_remote_retrieve_body( wp_remote_get( $url ) );
      if (isset(json_decode($tag_response)->pagination->min_tag_id)) {
        update_option('wallly_instagram_offset_tag', json_decode($tag_response)->pagination->min_tag_id , true);
      }
      if (isset(json_decode($tag_response)->data)) {
        $tag_response = json_decode($tag_response)->data;
      }
    }

    $response = "";

    if ( !empty($user_response) && !empty($tag_response) ){
      $response = array_merge($user_response, $tag_response);
    }
    elseif ( !empty($user_response) && empty($tag_response) ) {
      $response = $user_response;
    }
    elseif ( empty($user_response) && !empty($tag_response) ) {
      $response = $tag_response;
    }
    else{
      return;
    }



    
    foreach ($response as $gram) {
      if (isset($gram->caption)) {
        $formatted_response["instagram_" . $gram->caption->id] = array(
          'created_at' => $gram->created_time,
          'id' => $gram->id,
          'user' => array(
            'name' => $gram->user->full_name,
            'handle' => $gram->user->username,
            'image' => $gram->user->profile_picture
          ),
          'content' => $gram->caption->text,
          'media_url' => $gram->images->standard_resolution->url,
          'link' => $gram->link,
          'source' => 'Instagram'
        );
      }
    }
    if (count($formatted_response) > 0) {
      return $formatted_response;
    } else {
      return NULL;
    }
  }

  function linkify_status_text($status_text)
  {
    // linkify URLs
    $status_text = preg_replace(
      '/(https?:\/\/\S+)/',
      '<a href="\1" target="_blank">\1</a>',
      $status_text
    );

    // linkify twitter users
    $status_text = preg_replace(
      '/(^|\s)@(\w+)/',
      '\1@<a href="http://twitter.com/\2" target="_blank">\2</a>',
      $status_text
    );

    // linkify tags
    $status_text = preg_replace(
      '/(^|\s)#(\w*[^\x00-\x7F]*\w*)/',
      '\1#<a href="https://twitter.com/search?q=%23\2" target="_blank">\2</a>',
      $status_text
    );

    return $status_text;
  }

  function wally_output_activity_feed() {
    $search_criteria = array();
    $results = wallly($search_criteria, $_GET['refresh'] === 'true' ? true : false);
    $html = '';
    if (isset($results)) {
      foreach($results as $result ) {
        $html .= '<div class="wallly-post-wrap">';
          $html .= '<div class="wallly-post">';
          if ( $result->media_url != NULL) {
            $html .= '<div class="wallly-post-media">';
              if ($result->link != NULL) {
                $html .= '<a href="' . $result->link . '" target="_blank">';
              }
              $html .= '<img class="wallly-post-image" src="' . $result->media_url . '"/>';
              if ($result->link != NULL) {
                $html .= '</a>';
              }
            $html .= '</div>';
          }
          $html .= '<div class="wallly-content-wrapper">';
            $html .= '<div class="wallly-timestamp"><span class="js-wallly-timestamp">' . $result->created_at . '</span> on <span class="wallly-source-icon ' . $result->source . '"  title="' . $result->source . '"></span></div>';
              $html .= '<div class="wallly-content"><div>';
              $html .= '<p>' . linkify_status_text($result->content) . '</p>';
              $html .= '</div></div>';
              $html .= '<div class="wallly-source-wrapper">';
                $html .= '<div class="wallly-user-handle-wrap" title="' . $result->user->name . '">';
                $source_image = $source_image = plugin_dir_url(__FILE__) . "lib/instagram.png";
                if ($result->source == "Twitter"){
                  $source_image = plugin_dir_url(__FILE__) . "lib/twitter.png";
                }
                $html .= '<img class="wallly-social-image" src="' . $source_image . '" alt="' . $result->source . ' icon">';
                $html .= '<img class="wallly-user-profile-pic" src="' . $result->user->image . '" alt="' . $result->user->handle . ' profile picture">@' . $result->user->handle;
                $html .= '</div>';          
              $html .= '</div>';
            $html .= '</div>';
          $html .= '</div>';
        $html .= '</div>';
      }
      echo $html;
    }
    wp_die();
  }

  function wallly_enqueue_scripts () {
    wp_register_script('wallly-script', plugin_dir_url(__FILE__) . 'js/wallly-script.js',array('jquery'));

    wp_localize_script('wallly-script', 'ajaxurl', admin_url('admin-ajax.php'));
    wp_localize_script('wallly-script', 'timeout', get_option( 'wallly_settings' )['wallly_refresh_rate'] );
    wp_localize_script('wallly-script', 'max_results', get_option( 'wallly_settings' )['wallly_max_results'] );
    wp_localize_script('wallly-script', 'autorefresh', (isset(get_option( 'wallly_settings' )['wallly_autorefresh']) ? '1' : '0') );

    wp_enqueue_script('wallly-script');
  }

  add_action("wp_enqueue_scripts", "wallly_enqueue_scripts", 110);

  add_action('wp_ajax_wallly-feed', 'wally_output_activity_feed');
  add_action('wp_ajax_nopriv_wallly-feed', 'wally_output_activity_feed');
  
  add_shortcode('wallly_container', 'wallly_output_container');

  function wallly_output_container(){
    ob_start();
    include( plugin_dir_path( __FILE__ ) . "lib/content.php");
    $content = ob_get_clean();
    return $content;  
  }
?>