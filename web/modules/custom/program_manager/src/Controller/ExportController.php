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
        $node_storage = $this->entityTypeManager()->getStorage('node');
        
        $node = $node_storage->loadByProperties([
            'nid' => $nodeInput
        ])[$nodeInput];
        if ($node->bundle() == 'programmes_de_recherche'){
            $this->createCsvProgrammeRecherche(
                [
                    [
                        'requirement' => 'remove',
                        'value' => 'telecharger_un_fichier'
                    ],
                    [
                        'requirement' => 'equals',
                        'value' => [
                            'key' => 'adhesion',
                            'value' => 'Acceptée'
                        ]
                    ]
                ],
                '../export.csv',
                $node->id()
            );
            return new BinaryFileResponse('../export.csv');
        } else {
            throw new NotFoundHttpException();
        }
    }

    public function createExportZip()
    {
        $node_storage = $this->entityTypeManager()->getStorage('node');
        $nodeList = $node_storage->loadByProperties([
            'type' => 'programmes_de_recherche'
        ]);
        //list of the files to be included in the zip file.
        $fileList = [];
        foreach($nodeList as $node){
            $fileName = '..\\'.$node->id().'.csv';

            $fileList[] = $fileName;
            $validCsv = $this->createCsvProgrammeRecherche(
                [
                    [
                        'requirement' => 'remove',
                        'value' => 'telecharger_un_fichier'
                    ]
                ],
                $fileName,
                $node->id()
            );
            if (!$validCsv){
                array_pop($fileList);
            }
        }
        // kint($fileList, DRUPAL_ROOT);
        // die();
        $zipCreated = self::create_zip($fileList, DRUPAL_ROOT.'\..\export.zip', true);
        if ($zipCreated){
            return new BinaryFileResponse('..\export.zip');
        } else {
            throw new \Exception('Oh shit! Zipping did not work after all');
        }
    }

    /**
     * Function that creates a file named $fileName from the node id $nid
     * @param array $settings Array of parameters to take into account while creating the csv
     * format of one entry : [ 'requirement' => 'valueRequirement', 'value' => 'value' ]
     * requirement can be : 
     * - 'equals' : equals the value in the value index to be in the csv with the structure ['key' => 'keyValue', 'value' => 'value' ]
     * - 'remove' : remove the index in value form the csv
     * @param string $fileName position of the file to create.
     * @param string $nid number of the node to export. Only work if the nid is of a "programmes_de_recherche" node type
     * @return boolean true if the csv creation worked. Else false
     */
    private function createCsvProgrammeRecherche(array $settings,string $fileName, string $nid){
        
        $user_storage = $this->entityTypeManager()->getStorage('user');
        $webform_storage = $this->entityTypeManager()->getStorage('webform_submission');
        $node_storage = $this->entityTypeManager()->getStorage('node');
        
        $node = $node_storage->loadByProperties([
            'nid' => $nid
        ])[$nid];
        
        //check validity of node type
        if ($node->bundle() == 'programmes_de_recherche'){

            //get list of webform submitions
            $webform_submission = $webform_storage->loadByProperties([
                'entity_type' => 'node',
                'entity_id' => $node->id(),
            ]);

            $submission_data = array();
            $fileHandle = fopen($fileName, 'w');
            // kint($fileHandle);
            foreach ($webform_submission as $submission) {

                $array = $submission->getData();
                $userId = $submission->getOwnerId();
                $hotelId = $array['choix_d_un_hotel'];
                $flagAccepted = true;
                //setting of user name and hotel name
                $array['user'] = $user_storage->loadByProperties([
                    'uid' => $userId
                ])[$userId]->getAccountName();

                $array['choix_d_un_hotel'] = $node_storage->loadByProperties([
                    'nid' => $hotelId
                ])[$hotelId]->getTitle();

                //rows to remove
                foreach($settings as $removing){
                    if ($removing['requirement'] == 'remove'){
                        if (isset($array[$removing['value']])){
                            unset($array[$removing['value']]);
                        }
                    } else if ($removing['requirement'] == 'equals'){
                        if ($array[$removing['value']['key']] != $removing['value']['value']){
                            $flagAccepted = false;
                        }
                    } else {
                        throw new \UnexpectedValueException('The value : '. $removing['requirement']. 'is not a valid value for the "requirements" part. The only valid values are : "equals" and "remove"');
                    }
                }

                if($flagAccepted){
                    $submission_data[] = $array;
                }
            }
            //header
            if (count($submission_data) == 0){
                fclose($fileHandle);
                return false;
            }
            fputcsv($fileHandle, array_keys($array));
            foreach ($submission_data as $value) {
                //line
                fputcsv($fileHandle, $value);
            }
            fclose($fileHandle);
            return true;
        }
    }

    static public function getAccess(){
        $route_match = \Drupal::service('current_route_match');
        $goodUserPermission = \Drupal::currentUser()->hasPermission('access-webform-submission-log');
        $node;
        if ($route_match->getParameter('node') === null){
            $nid = $route_match->getParameter('nodeInput');
            $node_storage = \Drupal::service('entity_type.manager')->getStorage('node');
            $node = $node_storage->loadByProperties([
                'nid' => $nid
            ])[$nid];
        } else {
            $node = $route_match->getParameter('node');
        }
        $goodNodeBundle = $node->bundle() == "programmes_de_recherche";
        // var_dump($goodNodeBundle, $goodUserPermission);
        // die();
        return ($goodNodeBundle && $goodUserPermission) ? AccessResult::allowed() : AccessResult::forbidden();
    }

    /**
     *  Creates a zip file at the $destination precised.
     * @param array $files List of the files location to put in the zip
     * @param string $destination location of the zip file to create
     * @param boolean $overwrite overwrites existing file when true. Else returns false
     * @return boolean True if it worked, else false
     */
    static public function create_zip($files = array(),$destination = '',$overwrite = false) {
        //if the zip file already exists and overwrite is false, return false
        if(file_exists($destination) && !$overwrite) { return false; }
        //vars
        $valid_files = array();
        //if files were passed in...
        if(is_array($files)) {
            //cycle through each file
            foreach($files as $file) {
                //make sure the file exists
                if(file_exists($file)) {
                    $valid_files[] = $file;
                }
            }
        }
        //if we have good files...
        if(count($valid_files)) {
            //create the archive
            $zip = new \ZipArchive();

            $zipOverwrite = ($overwrite && file_exists($destination)) ? \ZipArchive::OVERWRITE : \ZipArchive::CREATE;
            if($zip->open($destination,$zipOverwrite) !== true) {
                return false;
            }
            //add the files
            foreach($valid_files as $file) {
                $zip->addFile($file,basename($file));
            }
            //debug
            // kint('The zip archive contains ',$zip->numFiles,' files with a status of ',$zip->status);
            
            // close the zip -- done!
            $zip->close();
            
            //check to make sure the file exists
            return file_exists($destination);
        }
        else
        {
            return false;
        }
    }
}