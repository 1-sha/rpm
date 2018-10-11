<?php

namespace Drupal\Tests\content_moderation_reviewer\Kernel;

class AccessCheckerTest extends CmrTestBase {

  /**
   * @return \Drupal\content_moderation_reviewer\AccessChecker
   */
  protected function getAccessChecker() {
    return \Drupal::service('content_moderation_reviewer.access_checker');
  }

  public function testDetermineReviewableWorkflowStatesForUser() {
    $this->assertEquals(['test_workflow' => ['draft', 'published']], $this->getAccessChecker()->determineReviewableWorkflowStatesForUser($this->users['test drafter 1']));
    $this->assertEquals(['test_workflow' => ['draft', 'published']], $this->getAccessChecker()->determineReviewableWorkflowStatesForUser($this->users['test drafter 2']));
    $this->assertEquals(['test_workflow' => ['published', 'editorial_review']], $this->getAccessChecker()->determineReviewableWorkflowStatesForUser($this->users['test editorial_reviewer 1']));
    $this->assertEquals(['test_workflow' => ['published', 'editorial_review']], $this->getAccessChecker()->determineReviewableWorkflowStatesForUser($this->users['test editorial_reviewer 2']));
    $this->assertEquals(['test_workflow' => ['published', 'legal_review']], $this->getAccessChecker()->determineReviewableWorkflowStatesForUser($this->users['test legal_reviewer 1']));
    $this->assertEquals(['test_workflow' => ['published', 'legal_review']], $this->getAccessChecker()->determineReviewableWorkflowStatesForUser($this->users['test legal_reviewer 2']));
  }

}
