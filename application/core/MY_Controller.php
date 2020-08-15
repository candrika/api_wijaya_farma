<?php
defined('BASEPATH') OR exit('No direct script access allowed');

date_default_timezone_set('Asia/Jakarta');

require APPPATH . 'libraries/REST_Controller.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

use GuzzleHttp\Client;

class MY_Controller extends REST_Controller{
    
    public $user_data;
    public $rest_client;
    public $rest_client_nusafin;
    public $user_id;
    public $unit_id;
    public $PhpSpreadsheet;
           
    function __construct() {
        parent::__construct();

        $this->user_id = null;
        $this->unit_id = null;

        $this->PhpSpreadsheet = new Spreadsheet();
        $this->PhpSpreadsheetBorder = new Border();
        $this->PhpSpreadsheetNumberFormat = new NumberFormat();
        $this->PhpSpreadsheetFill = new Fill();
        $this->PhpSpreadsheetXlsx = new Xlsx($this->PhpSpreadsheet);

        $this->load->model('m_user');
        $this->load->model('m_journal');

	    if(ENVIRONMENT=='development'){
            // define('NUSAFIN_API_URL','http://apidev.nusafin.com/');
        //     define('NUSAFIN_API_KEY','b54b4y554y');
        } else {           
            // define('NUSAFIN_API_URL','https://api.nusafin.com/');
            // define('NUSAFIN_API_KEY','b54b4y554y');
        }

        if($this->get('key')!=''){
             $auth = $this->m_data->auth_check($this->get('key'));
                if($auth==false){
                    $this->response(array('success'=>false,'message'=>'API Key not found'), REST_Controller::HTTP_BAD_REQUEST);
                } else {
                    $this->user_data = $auth;
                }
        } else if($this->post('key')!=''){
             $auth = $this->m_data->auth_check($this->post('key'));
                if($auth==false){
                    $this->response(array('success'=>false,'message'=>'API Key not found'), REST_Controller::HTTP_BAD_REQUEST);
                } else {
                    $this->user_data = $auth;
                }
        } else if($this->put('key')!=''){
             $auth = $this->m_data->auth_check($this->put('key'),$this->put('password'));
                if($auth==false){
                    $this->response(array('success'=>false,'message'=>'API Key not found'), REST_Controller::HTTP_BAD_REQUEST);
                } else {
                    $this->user_data = $auth;
                }
        } else if($this->delete('key')!=''){
             $auth = $this->m_data->auth_check($this->delete('key'));
                if($auth==false){
                    $this->response(array('success'=>false,'message'=>'API Key not found'), REST_Controller::HTTP_BAD_REQUEST);
                } else {
                    $this->user_data = $auth;
                }
        }  else if(isset($_SERVER['PHP_AUTH_USER'])){
            if($_SERVER['PHP_AUTH_USER']!='17091945'){
                //17091945 -> from internal source
                
                $auth = $this->m_data->auth_check($_SERVER['PHP_AUTH_USER']);
                if($auth==false){
                    $this->response(array('success'=>false,'message'=>'API Key not found'), REST_Controller::HTTP_BAD_REQUEST);
                } else {
                    $this->user_data = $auth;
                }
            }           
        } else {
             $this->response(array('success'=>false,'message'=>'API Key not found'), REST_Controller::HTTP_BAD_REQUEST);
        }

        // $key = $this->get('key');
        // if($key==''){
        //     $key = $this->post('key');
        // }
        // if($key==''){
        //     $key = $this->put('key');
        // }
        // if($key==''){
        //     $key = $this->delete('key');
        // }

        // if($key!=''){
        //     $account = $this->m_user->get_account($key);
        //     if(!$account){
        //         echo json_encode(array('success'=>false,'message'=>'key is invalid'));
        //         return false;
        //     } else {
        //         $this->user_id = $account['user_id'];
        //         $this->unit_id = $account['idunit'];
        //     }
        // }
        
        
    }
}
?>
