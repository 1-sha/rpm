(function ($) {
  Drupal.behaviors.agov_base_primary_navigation = {
    attach: function (context, settings) {
      $('.js-site-nav', context).once().each(function () {
        var $menu = $(this),
          $level_2_list = $menu.find('.site-nav__list-level-2');

        // Make it responsive.
        responsiveNav('.js-site-nav');

        // Enable keyboard navigation for accessibility.
        $level_2_list.find('a').focus(function() {
          $(this).closest($level_2_list).closest('li').addClass('opened');
        });
        $level_2_list.find('a').blur(function() {
          $(this).closest($level_2_list).closest('li').removeClass('opened');
        });
      });
    }
  };
})(jQuery);
