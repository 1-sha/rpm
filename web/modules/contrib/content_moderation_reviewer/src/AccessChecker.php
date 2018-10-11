<?php

namespace Drupal\content_moderation_reviewer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\RoleInterface;
use Drupal\user\UserInterface;
use Drupal\workflows\TransitionInterface;
use Drupal\workflows\WorkflowInterface;

/**
 * Provides a way for code to filter users by transitions they are allowed to do.
 */
class AccessChecker {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new StateTransitionValidation.
   * 
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Return users which can transition beyond the to_state.
   *
   * - When chosen a given state ID, this function returns role IDs
   *   which can move the content along to any future state.
   */
  public function roleIdsWithAllowedTransition($workflow_id, $from_state_id, $to_state_id) {
    assert(is_string($from_state_id));
    assert(is_string($to_state_id));

    if ($workflow = $this->entityTypeManager->getStorage('workflow')->load($workflow_id)) {
      assert($workflow instanceof WorkflowInterface);
      $transitions = $workflow->getTypePlugin()
        ->getTransitionsForState($to_state_id);

      $permissions = array_map(function (TransitionInterface $transition) use ($workflow){
        return "use {$workflow->id()} transition {$transition->id()}";
      }, $transitions);

      // Sadly config query doesn't support array values yet, so we have to
      // load all roles manually and filter them by permission.
      $role_storage = $this->entityTypeManager->getStorage('user_role');
      $roles = $role_storage->loadMultiple();
      $rids = array_map(function (RoleInterface $role) {
        return $role->id();
      }, array_filter($roles, function (RoleInterface $role) use ($permissions) {
        return !array_diff($permissions, $role->getPermissions());
      }));

      $admin_rids = $role_storage->getQuery()
        ->condition('is_admin', TRUE)
        ->execute();
      return array_merge($rids, $admin_rids);
    }
    return [];
  }

  /**
   * @todo find a better name
   */
  public function determineReviewableWorkflowStatesForUser(AccountInterface $account) {
    $allowed_states = [];

    foreach ($this->entityTypeManager->getStorage('workflow')->loadMultiple() as $workflow) {
      assert($workflow instanceof WorkflowInterface);

      foreach ($workflow->getTypePlugin()->getStates() as $state) {
        $transitions = $workflow->getTypePlugin()
          ->getTransitionsForState($state->id());

        $permissions = array_map(function (TransitionInterface $transition) use ($workflow) {
          return "use {$workflow->id()} transition {$transition->id()}";
        }, $transitions);

        // The user needs to be able to execute every transition.
        $has_access = array_reduce($permissions,
          function ($had_access, $permission) use ($account) {
            return $had_access && $account->hasPermission($permission);
          }, TRUE);
        if ($has_access) {
          $allowed_states[$workflow->id()][] = $state->id();
        }
      }
    }

    return $allowed_states;
  }

  /**
   * Validtes whether a user is a valid reviewer for the given to_state_id.
   */
  public function isValidReviewer($workflow_id, UserInterface $reviewer, $from_state_id, $to_state_id) {
    assert(is_string($workflow_id));
    $reviewer_role_ids = $reviewer->getRoles();
    $allowed_roles = $this->roleIdsWithAllowedTransition($workflow_id, $from_state_id, $to_state_id);
    return !empty(array_intersect($reviewer_role_ids, $allowed_roles));
  }

}
