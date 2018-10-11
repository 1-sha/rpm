<?php

namespace Drupal\Tests\content_moderation_reviewer\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @todo
 *
 * @see \Drupal\content_moderation_reviewer\Controller::reviewerAutocomplete
 *
 * @group content_moderation_reviewer
 */
class AutocompletionTest extends CmrTestBase {

  public function testValidTransition() {

    $node = Node::create([
      'type' => 'example',
      'title' => 'Test title',
    ]);
    $node->get('moderation_state')->value = 'draft';
    $node->save();
    assert($node instanceof NodeInterface);

    $http_kernel = \Drupal::service('http_kernel');
    assert($http_kernel instanceof HttpKernelInterface);

    $response = $http_kernel->handle(Request::create('/content_moderation_reviewer/test_workflow/draft/editorial_review', 'GET', ['q' => 'test']));

    $this->assertEquals(200, $response->getStatusCode());
    $this->assertJson($response->getContent());
    $this->assertEquals([
      [
        'value' => 'test editorial_reviewer 1 (3)',
        'label' => 'test editorial_reviewer 1',
      ],
      [
        'value' => 'test editorial_reviewer 2 (4)',
        'label' => 'test editorial_reviewer 2',
      ],
    ], json_decode($response->getContent(), TRUE));

    $response = $http_kernel->handle(Request::create('/content_moderation_reviewer/test_workflow/editorial_review/editorial_review', 'GET', ['q' => 'test']));

    $this->assertEquals(200, $response->getStatusCode());
    $this->assertJson($response->getContent());
    $this->assertEquals([
      [
        'value' => 'test editorial_reviewer 1 (3)',
        'label' => 'test editorial_reviewer 1',
      ],
      [
        'value' => 'test editorial_reviewer 2 (4)',
        'label' => 'test editorial_reviewer 2',
      ],
    ], json_decode($response->getContent(), TRUE));

    $response = $http_kernel->handle(Request::create('/content_moderation_reviewer/test_workflow/editorial_review/legal_review', 'GET', ['q' => 'test']));

    $this->assertEquals(200, $response->getStatusCode());
    $this->assertJson($response->getContent());
    $this->assertEquals([
      [
        'value' => 'test legal_reviewer 1 (5)',
        'label' => 'test legal_reviewer 1',
      ],
      [
        'value' => 'test legal_reviewer 2 (6)',
        'label' => 'test legal_reviewer 2',
      ],
    ], json_decode($response->getContent(), TRUE));

    $response = $http_kernel->handle(Request::create('/content_moderation_reviewer/test_workflow/legal_review/published', 'GET', ['q' => 'test']));

    $this->assertEquals(200, $response->getStatusCode());
    $this->assertJson($response->getContent());
    $this->assertEquals([
      [
        'value' => 'test drafter 1 (1)',
        'label' => 'test drafter 1',
      ],
      [
        'value' => 'test drafter 2 (2)',
        'label' => 'test drafter 2',
      ],
      [
        'value' => 'test editorial_reviewer 1 (3)',
        'label' => 'test editorial_reviewer 1',
      ],
      [
        'value' => 'test editorial_reviewer 2 (4)',
        'label' => 'test editorial_reviewer 2',
      ],
      [
        'value' => 'test legal_reviewer 1 (5)',
        'label' => 'test legal_reviewer 1',
      ],
      [
        'value' => 'test legal_reviewer 2 (6)',
        'label' => 'test legal_reviewer 2',
      ],
    ], json_decode($response->getContent(), TRUE));
  }

}
