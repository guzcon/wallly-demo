<div class="wallly_container">
<?php 
  $search_criteria = array(
   "hash_tag" => "ICTexpoHKI",
   "twitter_count" => 6,
   "instagram_count" => 6
   );
 $results = wallly($search_criteria); ?>
  <?php
  $html = "";
  foreach($results as $result ) {
    $html .= "<div class='col-md-3'>";
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
    // $html .= "<span class='social_user_time'>" . $result->created_at . "</span>";
    $html .= "<span class='pull-right'>" . $result->source . "</span>";
    
    $html .= "</div>";
    $html .= "</div>";
    $html .= "</div>";
    $html .= "</div>";
  }
  echo $html;

?>
</div>