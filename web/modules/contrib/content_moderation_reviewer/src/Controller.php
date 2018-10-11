<?php

namespace Drupal\content_moderation_reviewer;

use Drupal\content_moderation_reviewer\Plugin\Field\FieldWidget\ReviewerAutocomplete;
use Drupal\Core\Controller\ControllerBase;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides autocompletion for the reviewer based upon current workflow state.
 */
class Controller extends ControllerBase {

  /**
   * @var \Drupal\content_moderation_reviewer\AccessChecker
   */
  protected $accessChecker;


  /**
   * Controller constructor.
   */
  public function __construct() {
    $this->accessChecker = \Drupal::service('content_moderation_reviewer.access_checker');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('content_moderation_reviewer.access_checker')
    );
  }

  public function reviewerAutocomplete(Request $request, $workflow_id, $from_state, $to_state) {
    $role_ids = $this->accessChecker->roleIdsWithAllowedTransition($workflow_id, $from_state, $to_state);

    $string = $request->query->get('q');
    $user_storage = $this->entityTypeManager()->getStorage('user');
    $users = [];
    if ($role_ids) {
      $users =
        $user_storage->loadMultiple($user_storage->getQuery()
          ->condition('roles', $role_ids, 'IN')
          ->condition('name', $string, 'CONTAINS')
          ->execute()
        );
    }

    return new JsonResponse(array_values(array_map(function (UserInterface $user) {
      return [
        'value' => ReviewerAutocomplete::userToAutocompleteLabel($user),
        'label' => $user->label(),
      ];
    }, $users)));
  }

}
