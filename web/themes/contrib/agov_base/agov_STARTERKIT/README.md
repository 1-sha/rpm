# STARTERKIT

A starting point to creating a sub-theme for your website based on agov_base.

## Creating your aGov Sub Theme

* Copy this agov_STARTERKIT folder (/profiles/agov/themes/agov/agov_STARTERKIT)
  * Paste it into /themes/custom
* Rename the copied folder to MY_THEME (this can be any machine name you like, but we'll use MY_THEME as the example.)
* Within this copy, rename the following files
  * Rename the agov_STARTERKIT.info.yml file to MY_THEME.info.yml
  * Rename the agov_STARTERKIT.libraries.yml to MY_THEME.libraries.yml
* Edit the newly renamed MY_THEME.info.yml
  * Change the following lines to suit your needs
    * `name = My Custom Theme`
    * `description = My custom aGov subtheme`
    * libraries 
      * Rename `agov_STARTERKIT/base` to `MY_THEME/base`
* Enable your subtheme
  * visit /admin/appearance
  * Click “Install and set default” on the appropriate subtheme you’ve created.
  * Visit your aGov site. Without any custom CSS this theme will be a black and white version of the default theme (agov_whitlam)
* Customise your theme. Check [the docs](https://github.com/previousnext/agov/blob/8.x-1.x/agov/docs/theming.md) for help.

## A note on inheritance

Because this is a sub-theme of agov_base it will inherit everything that is in agov_base.
It is designed to sit on-top of agov_base allowing you to easily style your aGov site.
You can disable any stylesheet that agov_base includes by editing your MY_THEME.info.yml file,
 and following the directions in either the `stylesheets-remove` section or the `libraries-override` section.

## More information

For more information about theming in Drupal 8, please visit [this theming guide](https://www.drupal.org/theme-guide/8)
