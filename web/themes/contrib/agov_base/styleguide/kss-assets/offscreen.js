(function () {

  // Define variables.
  var $toggleLink   = $('.kss-offscreen__toggle');
  var $offscreen    = $('.kss-offscreen');
  var desktopWidth  = 769;

  // Toggle open by default.
  // Classes set in index.hbs
  var toggleOpen    = true;

  // Do the toggle.
  var toggleMenu = function () {
    if (toggleOpen) {
      $offscreen.removeClass('is-moved');
      $toggleLink.removeClass('is-active').attr('aria-expanded', 'false');
    }
    else {
      $offscreen.addClass('is-moved');
      $toggleLink.addClass('is-active').attr('aria-expanded', 'true');
    }
    toggleOpen = !toggleOpen;
  };

  // Open the menu when clicking the toggle link.
  $toggleLink.click(function () {
    toggleMenu();
    return false;
  });

  // Close the menu on page load if desktop width.
  if (window.innerWidth <= desktopWidth) {
    toggleOpen = true;
    toggleMenu();
  }

  // Close the menu on on the templates section.
  var sectionTitle = $offscreen.find('h1.kss-title').text();
  if (sectionTitle.indexOf('Templates') >= 0 && window.innerWidth >= desktopWidth) {
    toggleOpen = true;
    toggleMenu();
  }

})();
