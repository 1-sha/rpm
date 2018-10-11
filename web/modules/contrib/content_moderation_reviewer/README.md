# Content moderation reviewer

The module allows to assign people to review a piece of content.

## Usage instructions

* Install the content moderation reviewer module
* Create your workflow using the workflow and content\_moderation module.
* Ensure to grant permissions to the various transitions in your workflow.
* When an author creates new content they can choose people to be assigned as reviewer which can
  change the workflow state in all directions.

## Site builder usage

* When you create views you can choose to add a relationship to the moderation reviewer
* This allows you to filter by content the current user is assigned to etc.

In general it might be worth checking out the example in the cmr\_test module.
This adds a workflow, assosiated roles as well as some test views.

## Development

* The module is a usual PHP based Drupal module

### Running tests

* To run the tests use [phpunit](https://www.drupal.org/docs/8/phpunit/running-phpunit-tests)
