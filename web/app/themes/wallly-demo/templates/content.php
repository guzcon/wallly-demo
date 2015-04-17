<!-- <article <?php post_class(); ?>>
  <header>
    <h2 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
    <?php get_template_part('templates/entry-meta'); ?>
  </header>
  <div class="entry-summary">
    <?php the_excerpt(); ?>
  </div>
</article>
 -->

 <?php 
  $search_criteria = array(
   "hash_tag" => "ICTexpoHKI",
   "twitter_count" => 2,
   "instagram_count" => 2
   );
 $results = wallly($search_criteria); ?>
  <?php
  $html = "";
  foreach($results as $result ) {
    $html .= "<div class='social_post'>";
    if ( $result->media_url != NULL) {
      $html .= "<div class='social_background_image'>";
      $html .= "<img src='" . $result->media_url . "'/>";
      $html .= "</div>";
    }
    $html .= "<div class='social_content_wrapper'>";
    $html .= "<p class='social_content'>";
    $html .= $result->content;
    $html .= "</p>";
    $html .= "<div class='social_info'>";
    $html .= "<span class='social_user_handle'>" . $result->handle . "</span>";
    $html .= "<span class='social_user_time'>" . $result->created_at . "</span>";
    $html .= "</div>";
    $html .= "</div>";
    $html .= "</div>";
  }
  echo $html;

?>  