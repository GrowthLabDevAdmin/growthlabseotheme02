  <?php
  if (!defined('ABSPATH')) {
    exit;
  }
  ?>

  <footer id="site-footer" role="contentinfo" class="site-footer">

    <?php
    global $post;
    $post_id = $post ? $post->ID : 0;

    $options = get_current_language_options();
    foreach ($options as $key => $value) $$key = $value;

    $phone_number = $contact_phone ?: $main_phone_number;
    if (get_field('custom_footer')) $form_section = get_field('form_section');
    if (get_field('custom_footer')) $content_section = get_field('content_section');
    if (get_field('custom_footer')) $copyright_section = get_field('copyright_section');
    ?>

    <?php
    if (!$form_section['hide_section']):
      foreach ($form_section as $form_field => $form_content) $$form_field = $form_content;
      $offices =  isset($show_offices) && $show_offices === "custom" ? $offices : $options['offices'];
    ?>
      <section class="contact-form-footer">

        <?php if (!empty($offices)): ?>
          <div class="contact-form-footer__offices">
            <ul class="footer-offices-selector">
              <?php foreach ($offices as $office): ?>
                <li class="footer-offices-selector__item btn btn--secondary" data-office="<?= esc_attr($office['city']); ?>">
                  <span>
                    <?= esc_html($office['city']); ?>
                  </span>
                </li>
              <?php endforeach ?>
            </ul>

            <div class="contact-form-footer__offices-wrapper">
              <?php foreach ($offices as $office): ?>
                <div class="footer-office" data-office="<?= esc_attr($office['city']); ?>">
                  <div class="footer-office__inner">
                    <?php
                    get_template_part('template-parts/google', 'maps', array(
                      'iframe_src' => $office['google_maps_embed_code'],
                      'classes' => 'footer-office__map'
                    ));
                    ?>

                    <address class="footer-office__info">

                      <<?= $office["city_tag"] ?> class="footer-office__city">
                        <a href="<?= $office['target_page_url'] ?>" aria-label="<?= esc_attr($office['city']); ?>">
                          <?= esc_html($office['city']); ?>
                        </a>
                      </<?= $office["city_tag"] ?>>

                      <div class="footer-office__item">
                        <?php include get_stylesheet_directory() . '/assets/icons/icon-location-outlined.svg'; ?>
                        <p><?= esc_html($office['address']); ?></p>
                      </div>

                      <p class="footer-office__item">
                        <?php include get_stylesheet_directory() . '/assets/icons/icon-phone.svg'; ?>
                        <a href="<?= get_flat_number($office['phone']) ?>" aria-label="<?= esc_attr($office['phone']); ?>"><?= esc_html($office['phone']); ?></a>
                      </p>

                      <a href="<?= $office['cta_button']['url'] ?>" class="footer-office__cta btn btn--tertiary" target="<?= $office['cta_button']['target'] ?>" aria-label="<?= esc_attr($office['cta_button']['title']); ?>">
                        <span>
                          <?= esc_html($office['cta_button']['title']); ?>
                        </span>
                      </a>
                    </address>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <div class="contact-form-footer__wrapper">
          <div class="contact-form-footer__inner">

            <div class="contact-form">

              <?php
              print_title($contact_form_title, $contact_form_title_tag, "contact-form__title tx-center");
              ?>

              <div class="contact-form__description formatted-text tx-center">
                <?php echo wp_kses_post(wpautop($contact_form_description)); ?>
              </div>

              <div class="contact-form__form">
                <?php gravity_form($contact_form, display_title: false, display_description: false); ?>
              </div>

            </div>

          </div>

        </div>
      </section>
    <?php
    endif;
    ?>

    <?php
    if (!$content_section['hide_section']):
      foreach ($content_section as $content_field => $content_data) $$content_field = $content_data;
    ?>

      <section class="content-info-footer">
        <div class="content-info-footer__wrapper container">

          <div class="content-info-footer__col">
            <div class="formatted-text">
              <?= $first_column_content && isset($first_column_content) ? $first_column_content : '' ?>
            </div>
          </div>

          <div class="content-info-footer__col">
            <?php if ($logo && isset($logo)) img_print_picture_tag(img: $logo, classes: "footer-logo", max_size: "thumbnail") ?>

            <div class="content-info-footer__social">
              <?php get_template_part('template-parts/social', 'networks', ["social_networks" => $select_social_networks ?? null]); ?>
            </div>

            <?php if ($cta_button && isset($cta_button)): ?>
              <a href="<?= $cta_button['url'] ?>" class="btn btn--primary" target="<?= $cta_button['target'] ?>" aria-label="<?= esc_attr($cta_button['title']) ?>">
                <span>
                  <?= esc_html($cta_button['title']) ?>
                </span>
              </a>
            <?php endif; ?>
          </div>

          <div class="content-info-footer__col">
            <div class="formatted-text">
              <?= $last_column_content && isset($last_column_content) ? $last_column_content : '' ?>
            </div>
          </div>
        </div>
      </section>

    <?php
    endif;
    ?>

    <?php
    if (!$copyright_section['hide_section']):
      foreach ($copyright_section as $copy_field => $copy_content) $$copy_field = $copy_content;
    ?>
      <section class="copyright-footer">
        <div class="copyright-footer__wrapper container">
          <p class="copyright-footer__advertisement">
            <?= $copyright && isset($copyright) ? $copyright : '' ?>
          </p>

          <?php
          if (isset($footer_links_menu) && $footer_links_menu) {
            wp_nav_menu(
              array(
                'menu'  => $footer_links_menu,
                'container'          => 'nav',
                'container_class' => 'footer-nav',
                'menu_class'      => 'footer-nav__menu',
                'items_wrap'      => '<ul class="%2$s">%3$s</ul>',
                'link_before'          => '<span>',
                'link_after'              => '</span>'
              )
            );
          }
          ?>

          <a href="https://growthlabseo.com/" target="_blank" class="copyright-footer__logo" aria-label="Growth Lab SEO">
            <img src="<?= get_template_directory_uri() . "/assets/img/Growth-Lab-Logo.png" ?>" alt="Growth Lab SEO Logo" width="270" height="50">
          </a>

        </div>
      </section>
    <?php
    endif;
    ?>
  </footer>

  <?php wp_footer(); ?>

  </body>

  </html>