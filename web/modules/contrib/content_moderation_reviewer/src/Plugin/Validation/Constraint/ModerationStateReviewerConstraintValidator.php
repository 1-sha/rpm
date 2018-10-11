<?php

namespace Drupal\content_moderation_reviewer\Plugin\Validation\Constraint;

use Drupal\content_moderation_reviewer\AccessChecker;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks if a moderation state transition is valid.
 */
class ModerationStateReviewerConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The access checker.
   *
   * @var \Drupal\content_moderation_reviewer\AccessChecker
   */
  protected $accessChecker;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The moderation info.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * Creates a new ModerationStateConstraintValidator instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_information
   *   The moderation information.
   * @param \Drupal\content_moderation_reviewer\AccessChecker $access_checker
   *   The access checker.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModerationInformationInterface $moderation_information, AccessChecker $access_checker) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moderationInformation = $moderation_information;
    $this->accessChecker = $access_checker;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('content_moderation.moderation_information'),
      $container->get('content_moderation_reviewer.access_checker')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    $entity = $value->getEntity();
    assert($entity instanceof ContentEntityInterface);

    // Ignore entities that are not subject to moderation anyway.
    if (!$this->moderationInformation->isModeratedEntity($entity)) {
      return;
    }

    $workflow = $this->moderationInformation->getWorkflowForEntity($entity);

    // If a new state is being set and there is an existing state, validate
    // there is a valid transition between them.
    if (!$entity->isNew() && !$this->isFirstTimeModeration($entity)) {
      $original_entity = $this->entityTypeManager->getStorage($entity->getEntityTypeId())->loadRevision($entity->getLoadedRevisionId());
      if (!$entity->isDefaultTranslation() && $original_entity->hasTranslation($entity->language()->getId())) {
        $original_entity = $original_entity->getTranslation($entity->language()->getId());
      }

      // If the state of the original entity doesn't exist on the workflow,
      // we cannot do any further validation of transitions, because none will
      // be setup for a state that doesn't exist. Instead allow any state to
      // take its place.
      if (!$workflow->getTypePlugin()->hasState($original_entity->moderation_state->value)) {
        return;
      }

      if (!$entity->get('content_moderation_reviewer')->target_id) {
        return;
      }

      $new_state = $workflow->getTypePlugin()->getState($entity->moderation_state->value);
      $original_state = $workflow->getTypePlugin()->getState($original_entity->moderation_state->value);
      $new_reviewer = $entity->get('content_moderation_reviewer')->entity;
      assert(!$new_reviewer || ($new_reviewer instanceof UserInterface));

      if ($new_reviewer && !$this->accessChecker->isValidReviewer($workflow->id(), $new_reviewer, $original_state->id(), $new_state->id())) {
        $this->context->addViolation($constraint->message, [
          '%uid' => $new_reviewer->id(),
          '%from' => $original_state->label(),
          '%to' => $new_state->label(),
        ]);
      }
    }
  }

  /**
   * Determines if this entity is being moderated for the first time.
   *
   * If the previous version of the entity has no moderation state, we assume
   * that means it predates the presence of moderation states.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being moderated.
   *
   * @return bool
   *   TRUE if this is the entity's first time being moderated, FALSE otherwise.
   */
  protected function isFirstTimeModeration(EntityInterface $entity) {
    $original_entity = $this->moderationInformation->getLatestRevision($entity->getEntityTypeId(), $entity->id());

    if ($original_entity) {
      $original_id = $original_entity->moderation_state;
    }

    return !($entity->moderation_state && $original_entity && $original_id);
  }

}
