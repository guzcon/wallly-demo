<?php

namespace Roots\Sage;

use Roots\Sage\Config;
use Roots\Sage\Wrapper;

?>

<?php get_template_part('templates/head'); ?>
  <body <?php body_class(); ?>>
    <!--[if lt IE 9]>
      <div class="alert alert-warning">
        <?php _e('You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.', 'sage'); ?>
      </div>
    <![endif]-->
    <?php
      // do_action('get_header');
      // get_template_part('templates/header');
    ?>
    <div class="wrap container-fluid" role="document">
      <div class="content row">
        <h1 class="col-xs-12"><img class="ict-logo" src="<?php echo get_template_directory_uri() . '/assets/images/ICT-logo-nettisivuille.png'; ?>">Social Media Wall: Use the hashtag #ICTexpo</h1>
        <main class="main" role="main">
          <?php include Wrapper\template_path(); ?>
          <div class="col-sm-4">
            <img class="bottom-logo" src="<?php echo get_template_directory_uri() . '/assets/images/logo-ite.png'; ?>">
            <h2>Digitalise your business</h2>
          </div>
          <div class="col-sm-4">
            <img class="bottom-logo" src="<?php echo get_template_directory_uri() . '/assets/images/logo_bc-pixels.svg'; ?>">
            <h2>Create a web product</h2>
          </div>
          <div class="col-sm-4">
            <h1><a href="http://itewiki.fi/some">itewiki.fi/some</a></h1>
          </div>
        </main><!-- /.main -->        
      </div><!-- /.content -->
    </div><!-- /.wrap -->
    <?php
      // get_template_part('templates/footer');
      // wp_footer();
    ?>
  </body>
</html>
