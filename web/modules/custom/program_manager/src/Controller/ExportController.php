<?php

namespace Drupal\program_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Access\AccessResult;

class ExportController extends ControllerBase
{
    public function exportProgramAdhesions(string $nodeInput)
    {
        $user_storage = $this->entityTypeManager()->getStorage('user');
        $webform_storage = $this->entityTypeManager()->getStorage('webform_submission');
        $node_storage = $this->entityTypeManager()->getStorage('node');
        
        $node = $node_storage->loadByProperties([
            'nid' => $nodeInput
        ])[$nodeInput];
        // kint($node);
        // kint($node->bundle());
//check validity of node type
        if ($node->bundle() == 'programmes_de_recherche'){
            $webform_submission = $webform_storage->loadByProperties([
                'entity_type' => 'node',
                'entity_id' => $node->id(),
                ]);
            $submission_data = array();
            $fileHandle = fopen('../export.csv', 'w');
            foreach ($webform_submission as $submission) {
                $array = $submission->getData();
                
                $userId = $submission->getOwnerId();
            $array['user'] = $user_storage->loadByProperties([
                'uid' => $userId
            ])[$userId]->getAccountName();
                //replace hotel field with hotel name instead of id
            $hotelId = $array['choix_d_un_hotel'];
            $array['choix_d_un_hotel'] = $node_storage->loadByProperties([
                    'nid' => $hotelId
            ])[$hotelId]->getTitle();
            unset($array['telecharger_un_fichier']);
            $submission_data[] = $array;
            
            }
            //header
            fputcsv($fileHandle, array_keys($array));
            foreach ($submission_data as $value) {
                //line
                fputcsv($fileHandle, $value);
            }
            fclose($fileHandle);
            return new BinaryFileResponse('../export.csv');
        } else {
            throw new NotFoundHttpException();
        }
    }

    static public function getAccess(){
        $route_match = \Drupal::service('current_route_match');
        $goodNodeBundle = $route_match->getParameter('node')->bundle() == "programmes_de_recherche";
        $goodUserPermission = \Drupal::currentUser()->hasPermission('access-webform-submission-log');
        // var_dump($goodNodeBundle, $goodUserPermission);
        // die();
        return ($goodNodeBundle && $goodUserPermission) ? AccessResult::allowed() : AccessResult::forbidden();
        // return true;
      }
}