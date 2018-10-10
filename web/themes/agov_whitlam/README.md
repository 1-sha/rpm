# agov_whitlam

The standard representation of aGov. This theme applies a colour palette and font to agov_base, 
and is intended as a guide for how to customise aGov.

# Want to customise this theme?

* Copy this agov_whitlam folder (/profiles/agov/themes/agov/agov_whitlam)
  * Paste it into /themes/custom
* Rename the copied folder to MY_THEME (this can be any machine name you like, but we'll use MY_THEME as the example.)
* Within this copy, rename the following files
  * Rename the agov_whitlam.info.yml file to MY_THEME.info.yml
  * Rename the agov_whitlam.libraries.yml to MY_THEME.libraries.yml
* Edit the newly renamed agov_whitlam.info.yml
  * Change the following lines to suit your needs
    * `name = My Custom Theme`
    * `description = My custom aGov subtheme`
    * libraries 
      * Rename `agov_whitlam/base` to `MY_THEME/base`
* Enable your subtheme
  * visit /admin/appearance
  * Click “Install and set default” on the appropriate subtheme you’ve created.
  * Visit your aGov site. Without any customisation this theme will look no different from the one installed by default.
* Customise the theme. Check [the docs](https://github.com/previousnext/agov/blob/8.x-1.x/agov/docs/theming.md) for help.

## A note on inheritance

Because this is a sub-theme of agov_base it will inherit everything that is in agov_base.
It is designed to sit on-top of agov_base allowing you to easily style your aGov site.
You can disable any stylesheet that agov_base includes by editing the agov_whitlam.info.yml file,
 and following the directions in either the `stylesheets-remove` section or the `libraries-override` section.

## More information

For more information about theming in Drupal 8, please visit [this theming guide](https://www.drupal.org/theme-guide/8)
