<?php

namespace Drupal\Tests\content_moderation_reviewer\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\workflows\Entity\Workflow;

/**
 * @todo
 *
 * @see \Drupal\content_moderation\Plugin\Validation\Constraint\ModerationStateReviewerConstraintValidator
 *
 * @group content_moderation_reviewer
 */
class ConstraintValidatorTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'content_moderation_reviewer',
    'content_moderation',
    'workflows',
    'system',
    'node',
    'user',
  ];

    /**
   * Creates the editorial workflow.
   *
   * @return \Drupal\workflows\Entity\Workflow
   *   The editorial workflow entity.
   */
  protected function createEditorialWorkflow() {
    $workflow = Workflow::create([
      'type' => 'content_moderation',
      'id' => 'editorial',
      'label' => 'Editorial',
      'type_settings' => [
        'states' => [
          'draft' => [
            'label' => 'Draft',
            'published' => FALSE,
            'default_revision' => FALSE,
            'weight' => -5,
          ],
          'review' => [
            'label' => 'Review',
            'weight' => 5,
            'published' => FALSE,
            'default_revision' => FALSE,
          ],
          'published' => [
            'label' => 'Published',
            'published' => TRUE,
            'default_revision' => TRUE,
            'weight' => 0,
          ],
        ],
        'transitions' => [
          'draft_review' => [
            'label' => 'Draft to review',
            'from' => ['draft'],
            'to' => 'review',
            'weight' => 2,
          ],
          'review_draft' => [
            'label' => 'Review to draft',
            'from' => ['review'],
            'to' => 'draft',
            'weight' => 3,
          ],
          'review_published' => [
            'label' => 'Review to publish',
            'from' => ['review'],
            'to' => 'published',
            'weight' => 4,
          ],
          'create_new_draft' => [
            'label' => 'Create New Draft',
            'to' => 'draft',
            'weight' => 0,
            'from' => [
              'draft',
              'published',
            ],
          ],
          'publish' => [
            'label' => 'Publish',
            'to' => 'published',
            'weight' => 1,
            'from' => [
              'draft',
              'published',
            ],
          ],
        ],
      ],
    ]);
    $workflow->save();
    return $workflow;
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('content_moderation_state');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('system', 'sequences');
    $this->installSchema('node', 'node_access');
  }

  public function testValidTransition() {
    $node_type = NodeType::create([
      'type' => 'example',
    ]);
    $node_type->save();
    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'example');
    $workflow->save();

    $node = Node::create([
      'type' => 'example',
      'title' => 'Test title',
    ]);
    $node->get('moderation_state')->value = 'draft';
    $node->save();
    assert($node instanceof NodeInterface);

    // Create a couple of roles with the following access:
    // 1. Review to published
    $role__review_published = Role::create([
      'id' => 'review_published',
      'permissions' => [
        'use editorial transition review_published',
      ]
    ]);
    $role__review_published->save();
    $user__review_published = User::create([
      'name' => 'user__review_published',
      'status' => 1,
      'roles' => [$role__review_published->id()],
    ]);
    $user__review_published->save();

    // 2. Draft to review
    $role__draft_review = Role::create([
      'id' => 'draft_review',
      'permissions' => [
        'use editorial transition draft_review',
      ]
    ]);
    $role__draft_review->save();
    $user__draft_review = User::create([
      'name' => 'user__draft_review',
      'status' => 1,
      'roles' => [$role__draft_review->id()],
    ]);
    $user__draft_review->save();

    // 3. published to draft
    $role__published_draft = Role::create([
      'id' => 'published_draft',
      'permissions' => [
        'use editorial transition create_new_draft',
      ]
    ]);
    $role__published_draft->save();
    $user__published_draft = User::create([
      'name' => 'user__published_draft',
      'status' => 1,
      'roles' => [$role__published_draft->id()],
    ]);
    $user__published_draft->save();

    // 4. admin role
    $role__admin = Role::create([
      'id' => 'admin_role',
      'is_admin' => TRUE,
    ]);
    $role__admin->save();
    $user__admin = User::create([
      'name' => 'user__admin',
      'status' => 1,
      'roles' => [$role__admin->id()],
    ]);
    $user__admin->save();

    // We want to move the moderation state to review.
    // Given that the only two users which should be allowed to be set
    // as reviewer are $role__review_published and $role__admin
    // as they are the only ones which can execute any further transition.

    $to_state_id = 'review';
    $data_provider = [
      'draft->review user:review->published' => [$user__review_published, TRUE],
      'draft->review user:admin' => [$user__admin, TRUE],
      'draft->review user:draft->review' => [$user__draft_review, FALSE],
      'draft->review user:published->draft' => [$user__published_draft, FALSE],
    ];

    $node->get('moderation_state')->value = $to_state_id;
    foreach ($data_provider as $key => list($user, $valid)) {
      $node->get('content_moderation_reviewer')->target_id = $user->id();

      if ($valid) {
        $this->assertEmpty($node->validate(), $key);
      }
      else {
        $this->assertNotEmpty($node->validate(), $key);
      }
    }

    $node->get('moderation_state')->value = 'review';
    $node->save();

    $to_state_id = 'published';
    $data_provider = [
      'review->published user:review->published' => [$user__review_published, FALSE],
      'review->published user:admin' => [$user__admin, TRUE],
      'review->published user:draft->review' => [$user__draft_review, FALSE],
      'review->published user:published->draft' => [$user__published_draft, TRUE],
    ];

    $node->get('moderation_state')->value = $to_state_id;
    foreach ($data_provider as $key => list($user, $valid)) {
      $node->get('content_moderation_reviewer')->target_id = $user->id();

      if ($valid) {
        $this->assertEmpty($node->validate(), $key);
      }
      else {
        $this->assertNotEmpty($node->validate(), $key);
      }
    }
  }

}
