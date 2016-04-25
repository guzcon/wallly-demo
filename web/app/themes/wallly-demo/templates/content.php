<div class="wallly_container_wrap">
  <div class="wally_container_header row <?php echo isset($_GET['wallly_hide_header']) ? 'hidden' : ''; ?>">
    <div class="col-xs-4 wallly-img-header left">
      <img class="header-icon" src="<?php echo plugins_url() . '/wallly/lib/ICTexpo.svg'; ?>">
    </div>
    <div class="col-xs-4 wallly-img-header mid">
      <img class="header-icon" src="<?php echo plugins_url() . '/wallly/lib/itewiki.svg'; ?>">
    </div>
    <div class="col-xs-4 wallly-img-header right">
      <img class="header-icon" src="<?php echo plugins_url() . '/wallly/lib/boonconPIXELS.svg' ?>">
    </div>
  </div>
  <div class="wallly_container hidden row"></div>
  <!-- <div class="wally_container_footer <?php echo isset($_GET['wallly_hide_footer']) ? 'hidden' : ''; ?>">
    <div class="row">
      <div class="col-sm-4">
        <ul class="list-inline footer-credits">
          <li>
            <a href="http://www.ndbsevents.com" target="_blank"><img src="<?php echo get_template_directory_uri() . '/assets/images/ndbs2015_logo.png'; ?>" class="bottom-logo"></a>
          </li>
        </ul>
      </div>
      <div class="col-sm-4 text-center">
        <ul class="list-inline footer-credits">
          <li>
            #NDBS2015
          </li>
        </ul>
      </div>
      <div class="col-sm-4 text-right">
        <ul class="list-inline footer-credits">
          <li>
            Powered By
          </li>
          <li>
            <a href="http://pixels.fi" target="_blank"><img class="bottom-logo" src="<?php echo get_template_directory_uri() . '/assets/images/logo_bc-pixels-v1.1-invert.svg'; ?>"></a>
          </li>
        </ul>
        <!-- <h1><a href="http://itewiki.fi/some" target="_blank" class="bottom-link color-inverted">itewiki.fi/some</a></h1> -->
      </div>
    </div>
  </div>
</div>
