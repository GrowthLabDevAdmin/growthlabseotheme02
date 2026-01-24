  <?php
  if (!defined('ABSPATH')) {
    exit;
  }
  ?>

  <footer id="site-footer" class="site-footer">

    <?php
    $options = get_current_language_options();
    foreach ($options as $key => $value) $$key = $value;
    $phone_number = $contact_phone ?: $main_phone_number;
    ?>

    <?php
    if (!$form_section['hide_section'] && !get_field('hide_form_section')):
      foreach ($form_section as $form_field => $form_content) $$form_field = $form_content;
    ?>
      <section class="contact-form-footer">

        <div class="contact-form-footer__wrapper">
          <?php if ($background_image) img_print_picture_tag(img: $background_image, is_cover: true, classes: "contact-form-footer__bg bg-image gradient-overlay"); ?>

          <div class="contact-form-footer__inner container">

            <div class="contact-form shadow-box">

              <?php
              print_title($contact_form_title, $contact_form_title_tag, "contact-form__title tx-center");
              get_template_part('template-parts/ampersand', 'separator', array('classes' => 'contact-form__separator'));
              ?>

              <div class="contact-form__description formatted-text tx-center">
                <?php echo wp_kses_post(wpautop($contact_form_description)); ?>
              </div>

              <div class="contact-form__form">
                <?php gravity_form($contact_form, display_title: false, display_description: false); ?>
              </div>

              <div class="contact-form__message formatted-text tx-center flex-center">
                <?= $message_before_submit ?>
              </div>

            </div>

            <?php if ($side_picture) img_print_picture_tag(img: $side_picture,  classes: "contact-form-footer__side-pic shadow-box"); ?>
          </div>

        </div>
      </section>
    <?php
    endif;
    ?>

    <?php
    if (!$locations_section['hide_section'] && !get_field('hide_locations_section')):
      foreach ($locations_section as $form_field => $form_content) $$form_field = $form_content;
    ?>

      <section class="locations-footer bg-bicolor">
        <div class="locations-footer__wrapper container border-box">

          <div class="locations-footer__content tx-center">
            <?php
            print_title($locations_title, $locations_title_tag, "locations-footer__title");
            get_template_part('template-parts/ampersand', 'separator', array('classes' => 'locations-footer__separator'));
            echo $locations_main_content;
            ?>
          </div>

          <div class="locations-cards">

            <div class="location-card location-card--first">
              <div class="location-card__wrapper">
                <div class="location-card__inner flex-center">
                  <?php if ($first_card['logo']) img_print_picture_tag(img: $first_card['logo'], max_size: "thumbnail",  classes: "location-card__logo"); ?>
                  <div class="location-card__content tx-center">
                    <?= $first_card['content'] ?>
                  </div>
                </div>
              </div>
            </div>

            <?php
            $locations = $options['offices'];
            if (!empty($locations)):
            ?>
              <div class="locations-cards__carousel">
                <div class="splide">
                  <div class="splide__track">
                    <div class="splide__list">
                      <?php
                      foreach ($locations as $location) {
                        get_template_part('template-parts/location', 'card', array('location' => $location, 'classes' => 'splide__slide'));
                      }
                      ?>
                    </div>
                  </div>

                  <?php
                  get_template_part('template-parts/splide', 'navigation', array(
                    'nav_link' => $locations_page_link,
                    'classes' => 'locations-cards__arrows'
                  ));
                  ?>

                </div>
              </div>
            <?php
            endif;
            ?>

          </div>

        </div>
      </section>

    <?php
    endif;
    ?>

    <?php
    if (!$copyright_section['hide_section'] && !get_field("hide_copyright_section")):
      foreach ($copyright_section as $form_field => $form_content) $$form_field = $form_content;
    ?>
      <section class="copyright-footer">
        <div class="copyright-footer__wrapper container">

          <div class="copyright-footer__social">
            <?php get_template_part('template-parts/social', 'networks'); ?>
          </div>

          <?php
          wp_nav_menu(
            array(
              'menu'  => $footer_links_menu,
              'container'          => 'nav',
              'container_class' => 'footer-nav',
              'menu_class'      => 'footer-nav__menu tx-center',
              'items_wrap'      => '<ul class="%2$s">%3$s</ul>',
              'link_before'          => '<span>',
              'link_after'              => '</span>'
            )
          );
          get_template_part('template-parts/ampersand', 'separator', array('classes' => 'copyright-footer__separator'));
          ?>

          <a href="https://growthlabseo.com/" target="_blank" class="copyright-footer__logo">
            <img src="<?= get_stylesheet_directory_uri() . "/assets/img/Growth-Lab-Logo.png" ?>" alt="Growth Lab SEO Logo" width="270" height="50">
          </a>

          <p class="copyright-footer__advertisement tx-center">
            <?= $copyright ?>
          </p>

        </div>
      </section>
    <?php
    endif;
    ?>

  </footer>

  <?php wp_footer(); ?>

  </body>

  </html>