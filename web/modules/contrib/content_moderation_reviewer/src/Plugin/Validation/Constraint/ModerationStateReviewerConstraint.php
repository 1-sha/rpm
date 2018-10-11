<?php

namespace Drupal\content_moderation_reviewer\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Verifies that the set reviewer is valid.
 *
 * @Constraint(
 *   id = "ModerationStateReviewer",
 *   label = @Translation("Valid moderation reviewer", context = "Validation")
 * )
 */
class ModerationStateReviewerConstraint extends Constraint {

  public $message = 'Invalid moderation reviewer  from %from to %to';

}
