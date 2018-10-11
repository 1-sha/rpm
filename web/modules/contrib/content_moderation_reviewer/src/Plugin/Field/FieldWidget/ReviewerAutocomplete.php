<?php

namespace Drupal\content_moderation_reviewer\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'entity_reference_autocomplete' widget.
 *
 * @FieldWidget(
 *   id = "content_moderation_reviewer_autocomplete",
 *   label = @Translation("Reviewer Autocomplete"),
 *   description = @Translation("An autocomplete text field."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class ReviewerAutocomplete extends WidgetBase {

  protected static function ajaxWrapperId($field_name, array $element) {
    return $field_name . '-content-moderation-reviewer';
  }

  /**
   * @return \Drupal\content_moderation\ModerationInformation
   */
  protected function getModerationInformation() {
    return \Drupal::service('content_moderation.moderation_information');
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    assert($items instanceof EntityReferenceFieldItemListInterface);
    $build = ['target_id' => []];
    $entity = $form_state->getFormObject()->getEntity();
    assert($entity instanceof ContentEntityInterface);

    if ($workflow = $this->getModerationInformation()->getWorkflowForEntity($entity)) {
      $from_state = $entity->get('moderation_state')->value;
      if (!empty($form_state->getUserInput()['moderation_state'][0]['state'])) {
        $to_state = $form_state->getUserInput()['moderation_state'][0]['state'];
      }
      elseif ($form_state->has(['moderation_state', 0, 'state'])) {
        $to_state = $form_state->get(['moderation_state', 0, 'state']);
      }
      else {
        $to_state = $entity->get('moderation_state')->value;
      }

      $build['target_id']['#type'] = 'textfield';
      $build['target_id']['#title'] = $items->getFieldDefinition()->getLabel();

      $referenced_entities = $items->referencedEntities();

      $build['target_id']['#default_value'] = isset($referenced_entities[$delta]) ? static::userToAutocompleteLabel($referenced_entities[$delta]) : '';
      $build['target_id']['#autocomplete_route_name'] = 'content_moderation_reviewer.autocomplete';
      $build['target_id']['#autocomplete_route_parameters'] = [
        'workflow_id' => $workflow->id(),
        'from_state' => $from_state,
        'to_state' => $to_state,
      ];
      $form['moderation_state']['#attributes']['id'] = static::ajaxWrapperId('content_moderation_reviewer', $form);

      $form['#process'][] = [__CLASS__, 'process'];
    }

    return $build;
  }

  public static function userToAutocompleteLabel(UserInterface $user) {
    return "{$user->label()} ({$user->id()})";
  }

  public static function process($element) {
    $element['moderation_state']['content_moderation_reviewer'] = $element['content_moderation_reviewer'];

    $element['moderation_state']['widget'][0]['state']['#ajax'] = [
      'callback' => [__CLASS__, 'updateWidget'],
      'wrapper' => static::ajaxWrapperId('content_moderation_reviewer', $element),
    ];

    unset($element['content_moderation_reviewer']);

    return $element;
  }

  public static function updateWidget($element) {
    return $element['moderation_state'];
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return parent::isApplicable($field_definition) && $field_definition->getFieldStorageDefinition()->getName() === 'content_moderation_reviewer';
  }

    /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, FormStateInterface $form_state) {
    return $element['target_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $key => $value) {
      $values[$key]['target_id'] = EntityAutocomplete::extractEntityIdFromAutocompleteInput($value['target_id']);
    }

    return $values;
  }

}
