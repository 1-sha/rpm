<?php

namespace Drupal\Tests\content_moderation_reviewer\Kernel;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\token\Kernel\KernelTestBase;
use Drupal\user\Entity\User;

class CmrTestBase extends KernelTestBase {

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
   * @var AccountInterface[]
   */
  protected $users;

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

    NodeType::create([
      'type' => 'page',
    ])->save();

    // Ensure we have an admin user so cmr_test_install() doesn't create admin users.
    User::create([
      'name' => 'admin',
    ])->save();

    \Drupal::service('module_installer')->install(['cmr_test']);

    $user_names = ['test drafter 1', 'test drafter 2', 'test editorial_reviewer 1', 'test editorial_reviewer 2', 'test legal_reviewer 1', 'test legal_reviewer 2'];
    $user_storage = \Drupal::entityTypeManager()->getStorage('user');
    foreach ($user_names as $name) {
      $users = $user_storage->loadMultiple($user_storage->getQuery()->condition('name', $name)->execute());
      $this->users[$name] = reset($users);
    }

    \Drupal::service('account_switcher')->switchTo($this->users['test drafter 1']);
  }

}
