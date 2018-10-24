<?php

namespace Drupal\program_manager\Plugin\Menu;

use Drupal\Core\Menu\LocalTaskDefault;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;
/**
 * Defines dynamic local tasks.
 */
class DynamicExportTask extends LocalTaskDefault 
{
  /**
   * {@inheritdoc}
   */
  public function getTitle(Request $request = NULL) {

    // Take custom 'config_translation_plugin_id' plugin definition key to
    // retrieve title. We need to retrieve a runtime title (as opposed to
    // storing the title on the plugin definition for the link) because
    // it contains translated parts that we need in the runtime language.
    
    return 'Export Adhesions';
  }

  public function getRouteParameters(RouteMatchInterface $route_match){

    // kint($route_match);
    return ['nodeInput' => $route_match->getParameter('node')->id()];

  }

  
}
