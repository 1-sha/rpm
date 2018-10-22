<?php

namespace Drupal\program_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends ControllerBase
{
    public function exportProgramAdhesions(string $id)
    {
        $user_storage = $this->entityTypeManager()->getStorage('user');
        $webform_storage = $this->entityTypeManager()->getStorage('webform_submission');
        $node_storage = $this->entityTypeManager()->getStorage('node');
        
        $node = $node_storage->loadByProperties([
            'nid' => $id
        ])[$id];

        if ($node->bundle() != 'programmes_de_recherche'){
            kint($node->bundle());
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
        }
    }
}