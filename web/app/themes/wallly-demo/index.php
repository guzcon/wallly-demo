<?php //get_template_part('templates/page', 'header'); ?>

<?php if (!have_posts()) : ?>
  <div class="alert alert-warning">
    <?php _e('Sorry, no results were found.', 'sage'); ?>
  </div>
  <?php get_search_form(); ?>
<?php endif; ?>

<?php while (have_posts()) : the_post(); ?>
  <?php get_template_part('templates/content', get_post_type() != 'post' ? get_post_type() : get_post_format()); ?>
<?php endwhile; ?>

<?php the_posts_navigation(); ?>


 <?php 
 //  $search_criteria = array(
 //   "instagram_user_id" => "490106858",
 //   "twitter_user_name" => "boonconPIXELS",
 //   "hash_tag" => "cake",
 //   "twitter_count" => 2,
 //   "instagram_count" => 2
 //   );

 // $results = wallly($search_criteria); 
 
 //  $html = "";
 //  foreach($results as $result ) {
 //    $html .= "<div class='social_post'>";
 //    if ( $result->media_url != NULL) {
 //      $html .= "<div class='social_background_image'>";
 //      $html .= "<img src='" . $result->media_url . "'/>";
 //      $html .= "</div>";
 //    }
 //    $html .= "<div class='social_content_wrapper'>";
 //    $html .= "<p class='social_content'>";
 //    $html .= $result->content;
 //    $html .= "</p>";
 //    $html .= "<div class='social_info'>";
 //    $html .= "<span class='social_user_handle'>" . $result->handle . "</span>";
 //    $html .= "<span class='social_user_time'>" . $result->created_at . "</span>";
 //    $html .= "</div>";
 //    $html .= "</div>";
 //    $html .= "</div>";
 //  }

 //  echo $html;

?> 