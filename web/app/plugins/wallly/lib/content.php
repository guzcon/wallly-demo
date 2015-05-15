<div class="wallly_container_wrap">
  <div class="wally_container_header <?php echo isset($_GET['wallly_hide_header']) ? 'hidden' : ''; ?>">
    <h4><?php _e('Create your own social media wall with <a href="http://wall.ly/" title="Wall.ly">Wall.ly</a>', 'wallly'); ?>
      <img class="wallly-logo" src="<?php echo plugins_url('px-wallly-logo.png', __FILE__); ?>">
    </h4>
  </div>
  <div class="wallly_container hidden">
  </div>
  <div class="wally_container_footer <?php echo isset($_GET['wallly_hide_footer']) ? 'hidden' : ''; ?>">

  </div>
</div>