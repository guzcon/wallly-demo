<?php
  /*
    Plugin Name: Wall.ly Social Media Plugin
    Plugin URI: http://pixels.fi
    Description: The Friendly Social Media Wall
    Version: 1.0
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
    wp_enqueue_style( "wallly", plugin_dir_url(__FILE__) . "stylesheet/wallly_style.css" );
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

      update_option('wallly_twitter_offset_user', json_decode($user_response)->search_metadata->max_id);
      $user_response = json_decode($user_response)->statuses;
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

      update_option('wallly_twitter_offset_tag', json_decode($tag_response)->search_metadata->max_id);
      $tag_response = json_decode($tag_response)->statuses;
    }

    $response = array_merge($user_response, $tag_response);

    foreach ($response as $tweet) {
      // dpm($tweet);
      $formatted_response['twitter_' . $tweet->id] = array(
        'created_at' => strtotime($tweet->created_at),
        'id' => $tweet->id,
        'username' => $tweet->user->name,
        'handle' => $tweet->user->screen_name,
        'content' => $tweet->text,
        'media_url' => isset($tweet->entities->media) ? $tweet->entities->media[0]->media_url : NULL,
        'source' => 'twitter'
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

    $response = array_merge($user_response, $tag_response);
    foreach ($response as $gram) {
      if (isset($gram->caption)) {
        $formatted_response["instagram_" . $gram->caption->id] = array(
          'created_at' => $gram->created_time,
          'id' => $gram->id,
          'username' => $gram->user->full_name,
          'handle' => $gram->user->username,
          'content' => $gram->caption->text,
          'media_url' => $gram->images->standard_resolution->url,
          'link' => $gram->link,
          'source' => 'instagram'
        );
      }
    }
    if (count($formatted_response) > 0) {
      return $formatted_response;
    } else {
      return NULL;
    }
  }

  function wally_output_activity_feed() {
    $results = wallly(array(), $_GET['refresh'] === 'true' ? true : false);
    $html = '';
    if (isset($results)) {
      foreach($results as $result ) {
        $html .= "<div class='col-xs-12 col-sm-6 col-md-4 col-lg-3 wallly-post-wrap'>";
           $html .= "<div class='wallly-post'>";
        if ( $result->media_url != NULL) {
          $html .= "<div class='wallly-post-media'>";
          $html .= '<img class="img-responsive" src="' . $result->media_url . '"/>';
          $html .= "</div>";
        }
        $html .= "<div class='social_content_wrapper'>";
        $html .= "<p class='social_content'>";
        $html .= $result->content;
        $html .= "</p>";
        $html .= "<div class='social_info'>";
        $html .= "<span class='social_user_handle'>@" . $result->handle . "</span>";
        $html .= "<span class='social_user_time'>" . date_i18n( 'M j, Y @ G:i:s', date( $result->created_at ) ) . "</span>";
        $html .= "<span class='pull-right'>" . $result->source . "</span>";
        
        $html .= "</div>";
        $html .= "</div>";
        $html .= "</div>";
        $html .= "</div>";
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
?>