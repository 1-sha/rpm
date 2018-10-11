<?php

namespace Drupal\content_moderation_reviewer\Plugin\views\filter;

use Drupal\content_moderation_reviewer\AccessChecker;
use Drupal\Core\Database\Query\Condition;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filters content by content that is reviewable by the current user.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("content_moderation_reviewer__reviewable")
 */
class ReviewableFilter extends FilterPluginBase {

  /**
   * @var \Drupal\views\Plugin\views\query\Sql
   */
  public $query;

  /**
   * @var \Drupal\content_moderation_reviewer\AccessChecker 
   */
  protected $accessChecker;

  /**
   * {@inheritdoc}
   */
  public function __construct( array $configuration, $plugin_id, $plugin_definition, AccessChecker $accessChecker) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->accessChecker = $accessChecker;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('content_moderation_reviewer.access_checker')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    $workflow_states =  $this->accessChecker->determineReviewableWorkflowStatesForUser($this->view->getUser());
    $main_condition = new Condition('OR');

    foreach ($workflow_states as $workflow_id => $allowed_workflow_states) {
      $sub_condition = new Condition('AND');
      $sub_condition->condition('workflow', $workflow_id);
      $sub_condition->condition('moderation_state', $allowed_workflow_states, 'IN');
      $main_condition->condition($sub_condition);
    }

    $this->query->addWhere($this->options['group'], $main_condition);
  }


}
