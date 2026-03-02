<?php
defined('BASEPATH') or exit('No direct script access allowed');

$allowedOrigins = [
    'https://automate.moriitalia.vn',
    'https://portal.chantroituonglai.com',
    'null',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

class Apis extends ClientsController
{
     private $haravan_token = 'DAC0F729FCC5E9633362DC94C3A6ECD800FE1071CC0FE361B6D86CFC6E3A9E87';
   
    public function index()
    {
       echo 'Hi';
    }

    public function orders_crawl_mm(){
        // 
        $NgayKT = $this->input->post('NgayKT');
        $NgayBD = $this->input->post('NgayBD');
        $cookie = $this->input->post('full_cookie') ?: $this->input->post('cookie');

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://supplier.mmvietnam.com/Service.ashx',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_POSTFIELDS => array('method' => 'ThongKe_ThongTinNhanHang_Xem','MaQGD' => '["0","10010","10011","10012","10013","10014","10015","10016","10017","10018","10019","10020","10021","10022","10023","10024","10025","10026","10027","10028","10029","10030","10041","10050","20090","30002","30005","30008","30010","30012","30013","30015","30018","30020","30026","30030","30032","30033","30035","30040","30049","30054","30056","30060","30061","30063","30064","30066","30067","30070","30072","30074","30076","30079","30080","30082","30083","30086","30087","30090","30091","30096","30097","30100","30102","30103","30105","30106","30107","30108","30109","30111","30112","30113","30114","30115","30116","30117","30118","30119","30120","30122","30124","30125","30126","30127","30128","30132","30134","30135","30136","30137","30138","30139","30141","30142","30144","30145","30147","30148","30149","30150","30154","30155","30156","30157","30158","30159","30160","30162","30163","30164","30165","30166","30167","30168","30169","30172","30173","30174","30175","60051","60052","60053","60054","90065","90071","90072","90073","90074","90079","90080","90085","90087","mm"]','MaNCC' => '["24240","24464","26630"]','NgayKT' => $NgayKT,'NgayBD' => $NgayBD),
            CURLOPT_HTTPHEADER => array(
                'Connection: keep-alive',
                'DNT: 1',
                'Origin: https://supplier.mmvietnam.com',
                'Referer: https://supplier.mmvietnam.com/mm.html',
                'Sec-Fetch-Dest: empty',
                'Sec-Fetch-Mode: cors',
                'Sec-Fetch-Site: same-origin',
                'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36',
                'accept: */*',
                'accept-language: en-US,en;q=0.9,vi-VN;q=0.8,vi;q=0.7',
                'sec-ch-ua: "Google Chrome";v="105", "Not)A;Brand";v="8", "Chromium";v="105"',
                'sec-ch-ua-mobile: ?0',
                'sec-ch-ua-platform: "macOS"',
                'x-requested-with: XMLHttpRequest',
                'Cookie:' . $cookie
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        echo json_encode(
            array(
                'response' => json_decode($response),
                '_post' => $this->input->post()
            )
        );
    }

    public function orders_crawl_2_mm(){
        // 
        $NgayKT = $this->input->post('NgayKT');
        $NgayBD = $this->input->post('NgayBD');
        $cookie = $this->input->post('full_cookie') ?: $this->input->post('cookie');

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://supplier.mmvietnam.com/Service.ashx',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_POSTFIELDS => array(
                'method' => 'CuaHang_VanBanDen_Xem',
                'MaNCC' => '["24240","24464","26630"]',
                'NgayKT' => $NgayKT,
                'NgayBD' => $NgayBD,
                'NguoiGui' => '8935132500000',
                'KenhID' => 'DatHang',
            ),
            CURLOPT_HTTPHEADER => array(
                'Connection: keep-alive',
                'DNT: 1',
                'Origin: https://supplier.mmvietnam.com',
                'Referer: https://supplier.mmvietnam.com/mm.html',
                'Sec-Fetch-Dest: empty',
                'Sec-Fetch-Mode: cors',
                'Sec-Fetch-Site: same-origin',
                'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36',
                'accept: */*',
                'accept-language: en-US,en;q=0.9,vi-VN;q=0.8,vi;q=0.7',
                'sec-ch-ua: "Google Chrome";v="105", "Not)A;Brand";v="8", "Chromium";v="105"',
                'sec-ch-ua-mobile: ?0',
                'sec-ch-ua-platform: "macOS"',
                'x-requested-with: XMLHttpRequest',
                'Cookie:' . $cookie
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        echo json_encode(
            array(
                'response' => json_decode($response, true),
                '_post' => $this->input->post()
            )
        );
    }

    public function order_detail_crawl_mm(){
        // 
        $OrderNumber = $this->input->post('OrderNumber');
        $cookie = $this->input->post('full_cookie') ?: $this->input->post('cookie');
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://supplier.mmvietnam.com/Service.ashx',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_POSTFIELDS => array(
                'method' => 'CuaHang_VanBanDen_XemChiTiet',
                'StoreCode' => '90071',
                'OrderNumber' => $OrderNumber,
            ),
            CURLOPT_HTTPHEADER => array(
                'Connection: keep-alive',
                'DNT: 1',
                'Origin: https://supplier.mmvietnam.com',
                'Referer: https://supplier.mmvietnam.com/mm.html',
                'Sec-Fetch-Dest: empty',
                'Sec-Fetch-Mode: cors',
                'Sec-Fetch-Site: same-origin',
                'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36',
                'accept: */*',
                'accept-language: en-US,en;q=0.9,vi-VN;q=0.8,vi;q=0.7',
                'sec-ch-ua: "Google Chrome";v="105", "Not)A;Brand";v="8", "Chromium";v="105"',
                'sec-ch-ua-mobile: ?0',
                'sec-ch-ua-platform: "macOS"',
                'x-requested-with: XMLHttpRequest',
                'Cookie:' . $cookie
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
      

        /// Lấy SKU
        $json_orders = json_decode($response, true);
        if ($json_orders['IsError'] == false){
            $this->load->model('external_model');
          
            $new_order_data = [];
            $json_order_data = $json_orders['data'];
            foreach( $json_order_data as $order_data ){
                $barcode = $order_data['Barcode'];
                $articleCode = $order_data['ArticleCode'];
                
                if (!empty(trim($barcode))){
                    $result = $this->external_model->get_sku_from_barcode( $barcode );
                    if ($result && $result['sku'] != null){
                        $order_data['mori_sku'] = $result['sku'];
                    } else {
                        // Fallback: nếu không tìm được mapping bằng barcode, dùng ArticleCode
                        if (!empty(trim($articleCode))){
                            $order_data['mori_sku'] = $articleCode;
                        }
                    }
                } else {
                    // Nếu không có barcode, dùng ArticleCode
                    if (!empty(trim($articleCode))){
                        $order_data['mori_sku'] = $articleCode;
                    }
                }
                $new_order_data[] = $order_data; // Always keep all items
            }

            $json_orders['data'] = $new_order_data;
        }
  

        echo json_encode(
            array(
                'response' => $json_orders,
                '_post' => $this->input->post()
            )
        );
    }

    public function order_add_record(){
        $this->load->model('external_model');
        
        // Check required fields
        $uniquekey = $this->input->post('uniquekey');
        $rel = $this->input->post('rel');
        
        if (empty($uniquekey) || empty($rel)) {
            echo json_encode(array(
                'success' => false,
                'message' => 'uniquekey and rel are required fields'
            ));
            return;
        }

        // Handle optional fields with empty string to false conversion
        $root_id = $this->input->post('root_id');
        $target_id = $this->input->post('target_id');
        $data = $this->input->post('data');

        $record_data = array(
            "uniquekey" => $uniquekey,
            "rel" => $rel,
            "root_id" => !empty($root_id) ? $root_id : false,
            "target_id" => !empty($target_id) ? $target_id : false,
            "data" => !empty($data) ? $data : false
        );

        $result = $this->external_model->add_record($record_data);

        echo json_encode(array(
            'success' => true,
            'response' => $result,
            '_post' => $this->input->post()
        ));
    }

    public function order_get_record(){
        $this->load->model('external_model');
        $result = $this->external_model->get_record($this->input->post('uniquekey'));

        echo json_encode(
            array(
                'response' => $result != null ? $result : false,
                '_post' => $this->input->post()
            )
        );
    }

    public function order_update_record(){
        $this->load->model('external_model');
        $result = $this->external_model->update_record($this->input->post('uniquekey'), array(
            "target_id" => $this->input->post('target_id'),
        ));

        echo json_encode(
            array(
                'response' => $result,
                '_post' => $this->input->post()
            )
        );
    }

    public function order_update_record_data(){
        $this->load->model('external_model');
        $result = $this->external_model->update_record($this->input->post('uniquekey'), array(
            "data" => $this->input->post('data'),
        ));

        echo json_encode(
            array(
                'response' => $result,
                '_post' => $this->input->post()
            )
        );
    }

    public function get_sku_from_barcode(){
        $this->load->model('external_model');
        $result = $this->external_model->get_sku_from_barcode(array(
            "mapping_id" => $this->input->post('barcode'),
        ));

        echo json_encode(
            array(
                'response' => $result,
                '_post' => $this->input->post()
            )
        );
    }


    // AEON
    public function orders_crawl_aeon_check_exist(){
        $ponumber = $this->input->post('ponumber');
        $poId = $this->input->post('poId');
        $this->load->model('external_model');
        $uniquekey = $ponumber.$poId;
        $result = $this->external_model->get_record($uniquekey);
        return $result;
    }

    public function orders_crawl_aeon(){
         
        // echo $response;
        if (file_exists(APPPATH . 'libraries/App_simple_html_dom.php')) {
            // array_push($autoload['helper'], 'my_functions');
            include(APPPATH . 'libraries/App_simple_html_dom.php');
        }
        $cookie = $this->input->post('full_cookie') ?: $this->input->post('cookie');
        $string = $cookie;

        $start = strpos($string, "JSESSIONID=");
        if ($start === false) {
            echo json_encode(array(
                'error' => true,
                'message' => 'Missing JSESSIONID in cookie',
                '_debug' => array(
                    '_post' => $this->input->post(),
                    'cookie_raw' => $cookie
                )
            ));
            return;
        }
        $end = strpos($string, ";", $start);
        if ($end === false) {
            $end = strlen($string);
        }
        $jsessionid = substr($string, $start, $end - $start);

        $curl = curl_init();
        $array_numbers = array();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://aeonvn.b2b.com.my/esupplier/pages/po/ListPOPage.do',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'poId=&poNo=&buyerOrgId=AEON_VN&sortField=PO.orderDate&sortDirection=DESC&directPage=&sstart=&sstatus=%25&isExpired=-1&start=0&start1=1',
            CURLOPT_HTTPHEADER => array(
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                'Accept-Language: en-US,en;q=0.9,vi-VN;q=0.8,vi;q=0.7,zh-TW;q=0.6,zh-CN;q=0.5,zh;q=0.4',
                'Cache-Control: max-age=0',
                'Connection: keep-alive',
                'Content-Type: application/x-www-form-urlencoded',
                'Cookie: cookieLanguage=vi_VN; cookieCoId=CONTY0000000369; cookieUserId=THUYLINH; cookieRememberMe=Y; '.$jsessionid.'',
                'DNT: 1',
                'Origin: https://aeonvn.b2b.com.my',
                'Referer: https://aeonvn.b2b.com.my/esupplier/pages/po/ListPOPage.do',
                'Sec-Fetch-Dest: document',
                'Sec-Fetch-Mode: navigate',
                'Sec-Fetch-Site: same-origin',
                'Sec-Fetch-User: ?1',
                'Upgrade-Insecure-Requests: 1',
                'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36',
                'sec-ch-ua: "Not_A Brand";v="99", "Google Chrome";v="109", "Chromium";v="109"',
                'sec-ch-ua-mobile: ?0',
                'sec-ch-ua-platform: "macOS"',
                // 'Cookie: cookieLanguage=vi_VN; cookieCoId=CONTY0000000369; cookieUserId=THUYLINH; cookieRememberMe=Y;' . $cookie
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
       
    
        // $this->load->library('App_simple_html_dom.php');
        // include('simple_html_dom.php');
        // Load the HTML content into the parser
        // echo $response;
        // die();
        $html = str_get_html($response);

        
        // Find the first table in the HTML
        $actionLinks = $html->find('.actionLink');
       
        foreach ($actionLinks  as $actionLink ){
           $attr = $actionLink->href;
            $poNumber = $actionLink->plaintext;
            // Find the position of vieworder in $email
            $position = strpos($attr, 'viewOrder');

            // 
            if ($position !== false) {
                // Tìm thấy
                $text = $attr;
                // Define the pattern to match
                $pattern = "/'([\d]+?)'/";

                // Extract the number from the string
                preg_match($pattern, $text, $matches);

                // The extracted number is stored in the first capture group ($matches[1])
                $number = $matches[1];

                // Output the result
                if ($number){
                    $order_date_raw = $this->_aeon_get_order_date($number, $poNumber, $jsessionid);
                    $within_7_days = $this->_aeon_is_within_last_7_days($order_date_raw);

                    if ($within_7_days === true) {
                        array_push($array_numbers, array(
                            'poId' => $number, 
                            'poNumber' => $poNumber,
                            'order_date' => $order_date_raw
                        ));
                    } elseif ($within_7_days === false) {
                        // List is sorted by order date DESC, so we can stop early.
                        break;
                    }
                }
            }
        }
       

        echo json_encode(
            array(
                'response' => $array_numbers,
                // 'actionLinks' => $response,
                '_post' => $this->input->post()
            )
        );
    }

    public function orders_crawl_aeon_detail(){
        // echo $response;
        if (file_exists(APPPATH . 'libraries/App_simple_html_dom.php')) {
            // array_push($autoload['helper'], 'my_functions');
            include(APPPATH . 'libraries/App_simple_html_dom.php');
        }

        $cookie = $this->input->post('full_cookie') ?: $this->input->post('cookie');
       
        $string = $cookie;

        $start = strpos($string, "JSESSIONID=");
        if ($start === false) {
            echo json_encode(array(
                'error' => true,
                'message' => 'Missing JSESSIONID in cookie',
                '_debug' => array(
                    '_post' => $this->input->post(),
                    'cookie_raw' => $cookie
                )
            ));
            return;
        }
        $end = strpos($string, ";", $start);
        if ($end === false) {
            $end = strlen($string);
        }
        $jsessionid = substr($string, $start, $end - $start);
        
        $ponumber = $this->input->post('ponumber');
        $poId = $this->input->post('poId');
        // Test
        // $poId = '325296729';
        // $ponumber = '10041000843682';
        
        $array_numbers = array();
        $masked_items = array();
        $haravan_found = null;
        $haravan_scanned = 0;
        $haravan_order = null;

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://aeonvn.b2b.com.my/esupplier/pages/po/ExportPO',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => 'sbuyerOrgId=AEON_VN&spoNo=null&sstatus=null&sRelatedDocNo=null&orderDateFrom=null&orderDateTo=null&deliveryDateFrom=null&deliveryDateTo=null&receivedDateFrom=null&receivedDateTo=null&sdeliveryLocName=null&sdeliveryLoc=null&sbrandName=null&sdepartmentName=null&sSupplierCodeName=null&sSupplierCodeId=null&sortField=null&sortDirection=null&directPage=null&actionType=null&sstart=null&isExpired=null&poId='.$poId.'&poNo='.$ponumber.'&url=POSharedPage.do&action1=View&buyerOrgId=AEON_VN&formatXsl=http%3A%2F%2Feportal-localhost.b2b.com.my%2FeportalConf%2FxslTemplates%2F%2FFormatOrder_AEON_VN.xsl&userId=CONTY0000000369%23THUYLINH&supplierOrgId=CONTY0000000369&exportedFileFormatOption=null&defaultExportedFileFormat=CSV&exportedFileFormat=CSV&actionType=export',
        CURLOPT_HTTPHEADER => array(
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'Accept-Language: en-US,en;q=0.9,vi-VN;q=0.8,vi;q=0.7,zh-TW;q=0.6,zh-CN;q=0.5,zh;q=0.4',
            'Cache-Control: max-age=0',
            'Connection: keep-alive',
            'Content-Type: application/x-www-form-urlencoded',
            'Cookie: cookieLanguage=vi_VN; cookieCoId=CONTY0000000369; cookieUserId=THUYLINH; cookieRememberMe=Y; '.$jsessionid,
            'DNT: 1',
            'Origin: https://aeonvn.b2b.com.my',
            'Referer: https://aeonvn.b2b.com.my/esupplier/pages/po/POSharedPage.do?poId='.$poId.'&buyerOrgId=AEON_VN&actionPage=View',
            'Sec-Fetch-Dest: document',
            'Sec-Fetch-Mode: navigate',
            'Sec-Fetch-Site: same-origin',
            'Sec-Fetch-User: ?1',
            'Upgrade-Insecure-Requests: 1',
            'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36',
            'sec-ch-ua: "Not_A Brand";v="99", "Google Chrome";v="109", "Chromium";v="109"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "macOS"'
        ),
        ));

        $response = curl_exec($curl);
        $curl_error = curl_error($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);
        if ($response === false || $http_code !== 200) {
            log_message('error', 'AEON ExportPO failed: http=' . $http_code . ' error=' . $curl_error . ' poId=' . $poId . ' ponumber=' . $ponumber);
            echo json_encode(array(
                'error' => true,
                'message' => 'AEON ExportPO failed',
                'http_code' => $http_code,
                'curl_error' => $curl_error,
                '_debug' => array(
                    '_post' => $this->input->post(),
                    'cookie_raw' => $cookie,
                    'jsessionid' => $jsessionid
                )
            ));
            return;
        }
        // ('error', 'AEON Order: '. $response);
        // // DEBUGING
        // echo json_encode(array(
        //         'response' => $response,
        //         'cookie' => $cookie,
        //         'ponumber' => $ponumber,
        //         'poId' => $poId,
        //         'cookie' => 'Cookie: cookieLanguage=vi_VN; cookieCoId=CONTY0000000369; cookieUserId=THUYLINH; cookieRememberMe=Y; '.$cookie,
        //         'CURLOPT_POSTFIELDS' => 'sbuyerOrgId=AEON_VN&spoNo=null&sstatus=null&sRelatedDocNo=null&orderDateFrom=null&orderDateTo=null&deliveryDateFrom=null&deliveryDateTo=null&receivedDateFrom=null&receivedDateTo=null&sdeliveryLocName=null&sdeliveryLoc=null&sbrandName=null&sdepartmentName=null&sSupplierCodeName=null&sSupplierCodeId=null&sortField=null&sortDirection=null&directPage=null&actionType=null&sstart=null&isExpired=null&poId='.$poId.'&poNo='.$ponumber.'&url=POSharedPage.do&action1=View&buyerOrgId=AEON_VN&formatXsl=http%3A%2F%2Feportal-localhost.b2b.com.my%2FeportalConf%2FxslTemplates%2F%2FFormatOrder_AEON_VN.xsl&userId=CONTY0000000369%23THUYLINH&supplierOrgId=CONTY0000000369&exportedFileFormatOption=null&defaultExportedFileFormat=CSV&exportedFileFormat=CSV&actionType=export',
        // ));
        // die();
        // END DEBUGIN
        $text = $response;
        // $lines = explode("\n", $text);
        // $header = explode(",", $lines[1]);

        // $masked_header = array();
        // $masked_items = array();
        // foreach ($header as $h){
        //     $masked_header[] =  str_replace('"', '', $h);
        // }

        // $data = array();
        // $pos = 0;
        // foreach ($lines as $line) {
        //     if (strpos($line, 'D') !== false && strpos($line, 'SD') == false &&  $pos != 1) {
        //         $values = explode(",", $line);
        //         $data[] = array_combine($masked_header, $values);
        //     }
        //     $pos++;
        // }

        $lines = explode("\n", $text);
        $header = isset($lines[1]) ? str_getcsv($lines[1]) : array();

        $masked_header = array();
        foreach ($header as $h){
            $masked_header[] =  str_replace('"', '', $h);
        }

        $data = array();
        $pos = 0;
        foreach ($lines as $line) {
            if (strpos($line, 'D') !== false && strpos($line, 'SD') == false &&  $pos != 1) {
                $values = str_getcsv($line);
                if ($masked_header && $values) {
                    $data[] = array_combine($masked_header, $values);
                }
            }
            $pos++;
        }

        // DEBUGING
        // echo json_encode( $data );
        // die();
        // END DEBUGING    

        if ( $data && count($data) > 0){
            $this->load->model('external_model');
            // Mapping sản phẩm
            $mpos = 0;
            foreach ($data as $order_item){
                // log_message('error', 'AEON Line: '. json_encode($order_item));

                if ($order_item){
                    $m_order_item = $order_item;
                    $sku = str_replace('"', '', $order_item['Ma vach']);
                    $result = $this->external_model->get_sku_from_barcode_v2($sku,"aeon_sku");
                    
                    // // echo '123';
                    // echo json_encode( array(
                    //     'result' => $result,
                    //     'sku' => $sku
                    // ) );
                    if ($result){  
                        $m_order_item['Ma vach'] = $result['sku'];
                        // $masked_items[] = $result;
                        // $data[$mpos] = 
                        // echo json_encode( $result );
                    } else {
                        $seach_sku = $this->external_model->get_sku_from_barcode_v2($sku,"fast_barco");
                        if ($seach_sku){  
                            $m_order_item['Ma vach'] = $seach_sku['sku'];
                        }
                    }
                    $masked_items[] = $m_order_item;
                    // echo json_encode(  $sku );
                }
               
            }
          
        }
        // die();
        // Kiểm tra thông tin đơn hang
       
        $this->load->model('external_model');
        $uniquekey = $ponumber.$poId;
        $order = $this->external_model->get_record($uniquekey);

        // Check Haravan orders by created date range (if possible)
        $order_date_raw = $data[0]['Ngay dat hang'] ?? null;
        if (!empty($ponumber)) {
            $haravan_result = false;
            if (!empty($order_date_raw)) {
                $tz = new DateTimeZone('Asia/Ho_Chi_Minh');
                $dt = DateTime::createFromFormat('Ymd', $order_date_raw, $tz);
                if ($dt !== false) {
                    $start = clone $dt;
                    $start->setTime(0, 0, 0);
                    $end = clone $dt;
                    $end->setTime(23, 59, 59);
                    $haravan_result = $this->_search_haravan_order_by_order_name_in_range(
                        $ponumber,
                        $start->format(DateTime::ATOM),
                        $end->format(DateTime::ATOM)
                    );
                }
            }
            if ($haravan_result === false) {
                $haravan_result = $this->_search_haravan_order_by_order_name($ponumber);
            }
            if ($haravan_result) {
                $haravan_found = true;
                $haravan_order = $haravan_result['order'] ?? null;
                $haravan_scanned = $haravan_result['scanned'] ?? 0;
            } else {
                $haravan_found = false;
            }
        }

        $not_found_marker = 'NOT_FOUND';
        if ( !$order ){
            // Đơn hàng chưa tạo
            $result = $this->external_model->add_record(array(
                "uniquekey" => $uniquekey,
                "rel" => 'Order',
                "root_id" => $ponumber,
                "target_id" => false,
                "data" => null
            ));
    
            if ( $result ){
                echo json_encode( array(
                    'order' => array(
                        "id" => $result,
                        "uniquekey" => $uniquekey,
                        "rel" => 'Order',
                        "root_id" => $ponumber,
                        "target_id" => false,
                    ),
                    'items' => $masked_items,
                    'masked_items' => $masked_items,
                    'haravan' => array(
                        'found' => $haravan_found,
                        'scanned' => $haravan_scanned,
                    ),
                    '_debug' => array(
                        '_post' => $this->input->post(),
                        'http_code' => $http_code
                    )
                ));
                return;
            }
        }

        if ($order) {
            $existing_target_id = $order->target_id ?? null;
            if (empty($existing_target_id)) {
                if ($haravan_found === false) {
                    $this->external_model->update_record($uniquekey, ['data' => $not_found_marker]);
                }
            } else {
                $this->external_model->update_record($uniquekey, ['data' => null]);
            }
        }

        echo json_encode(array(
            'order_exists' => $order ? true : false,
            'items' => $masked_items,
            'masked_items' => $masked_items,
            'haravan' => array(
                'found' => $haravan_found,
                'scanned' => $haravan_scanned,
            ),
            '_debug' => array(
                '_post' => $this->input->post(),
                'http_code' => $http_code
            )
        ));
        return;
    }


    // Lotte
    public function orders_crawl_lotte_getcrsf(){
        if (file_exists(APPPATH . 'libraries/App_simple_html_dom.php')) {
            // array_push($autoload['helper'], 'my_functions');
            include(APPPATH . 'libraries/App_simple_html_dom.php');
        }
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://edilottemart.vn/security/securityOuForm',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'Accept-Language: en-US,en;q=0.9,vi-VN;q=0.8,vi;q=0.7,zh-TW;q=0.6,zh-CN;q=0.5,zh;q=0.4',
            'Cache-Control: max-age=0',
            'Connection: keep-alive',
            'DNT: 1',
            'Referer: https://edilottemart.vn/main/mainDashboard',
            'Upgrade-Insecure-Requests: 1',
            'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36'
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        // echo $response;
        $html = str_get_html($response);

        // Find the first table in the HTML
        $csrf_tag = $html->find('meta[name="_csrf"]', 0);
        echo json_encode( array(
            'csrf_tag' => $csrf_tag->content
        ));

        die();
    }

    public function orders_crawl_lotte(){
        $cookie = $this->input->post('full_cookie') ?: $this->input->post('cookie');
        $csrf_tag = $this->input->post('csrf_tag');

        $from = $this->input->post('from');
        $to = $this->input->post('to');
       
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://edilottemart.vn/app/po/splyord_selSearchList',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'_search=false&nd=1675931909250&rows=100&page=1&sidx=ORD_PROC_NM&sord=asc&_csrf='. $csrf_tag .'&venCd=&ordSlipNo=&ordFrDy='.$from.'&ordToDy='.$to.'&strCd=&ordProcCd=&splyFrDy=&splyToDy=',
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json, text/javascript, */*; q=0.01',
            'Accept-Language: en-US,en;q=0.9,vi-VN;q=0.8,vi;q=0.7,zh-TW;q=0.6,zh-CN;q=0.5,zh;q=0.4',
            'Connection: keep-alive',
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
            'Cookie: ' . $cookie,
            'DNT: 1',
            'Origin: https://edilottemart.vn',
            'Referer: https://edilottemart.vn/app/po/splyord?_code=10084',
            'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36',
            'X-CSRF-TOKEN: '. $csrf_tag,
            'X-Requested-With: XMLHttpRequest'
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;
    }

    public function orders_crawl_lotte_detail(){
        $cookie = $this->input->post('full_cookie') ?: $this->input->post('cookie');
        $csrf_tag = $this->input->post('csrf_tag');
        $ordSlipNo = $this->input->post('ordSlipNo');

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://edilottemart.vn/app/po/splyord_selSearchSubList',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{"ordSlipNo":"'.$ordSlipNo.'"}',
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json, text/javascript, */*; q=0.01',
            'Accept-Language: en-US,en;q=0.9,vi-VN;q=0.8,vi;q=0.7,zh-TW;q=0.6,zh-CN;q=0.5,zh;q=0.4',
            'Connection: keep-alive',
            'Content-Type: application/json; charset=UTF-8',
            'Cookie: ' . $cookie,
            'X-CSRF-TOKEN: '. $csrf_tag,
            'DNT: 1',
            'Origin: https://edilottemart.vn',
            'Referer: https://edilottemart.vn/app/po/splyord?_code=10084',
            'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36',
            'X-Requested-With: XMLHttpRequest'
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $response_json =  json_decode($response);
        // Kiểm tra thông tin đơn hang
        
        // Kiểm tra danh sách sản phẩm
        $this->load->model('external_model');
        // Mapping sản phẩm
        $masked_items = array();
        $data = $response_json;
       
        $mpos = 0;
        foreach ($data as $order_item){
            $m_order_item = $order_item;
            $sku = str_replace('"', '', $order_item->srcmkCd);
            $result = $this->external_model->get_sku_from_barcode_v2($sku,"fast_barco");
            // // echo '123';
            // echo json_encode( array(
            //     'result' => $result,
            //     'sku' => $sku
            // ) );
            if ($result){  
                $m_order_item->srcmkCd = $result['sku'];
                // $masked_items[] = $result;
                // $data[$mpos] = 
                // echo json_encode( $result );
            }
            $masked_items[] = $m_order_item;
            // echo json_encode(  $sku );
        }



         $uniquekey = 'LOTTE'.$ordSlipNo;
         $order = $this->external_model->get_record($uniquekey);
         if ( !$order ){
             // Đơn hàng chưa tạo
             $result = $this->external_model->add_record(array(
                 "uniquekey" => $uniquekey,
                 "rel" => 'Order',
                 "root_id" => $ordSlipNo,
                 "target_id" => false
             ));
     
             if ( $result ){
                 echo json_encode( array(
                    $response_json,
                     'order' => array(
                         "id" => $result,
                         "uniquekey" => $uniquekey,
                         "rel" => 'Order',
                         "root_id" => $ordSlipNo,
                         "target_id" => false,
                     ),
                 ));
             }
         } else {
            if (empty($order->target_id)) {
                 echo json_encode( array(
                    $response_json,
                     'order' => array(
                         "id" => $order->id,
                         "uniquekey" => $uniquekey,
                         "rel" => 'Order',
                         "root_id" => $ordSlipNo,
                         "target_id" => false,
                     ),
                 ));
            } else {
                echo json_encode( array(
                    $response_json, 'LOTTE'.$ordSlipNo
                 ));
            }
         }
    }

    public function import_suppliers() {
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $this->load->model('merchant_list_model');

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.accesstrade.vn/v1/offers_informations/merchant_list',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Token RWdYZRWaB7vR3lSrwqYAzmEn-h7dvt6-'
            ),
        ));

        $response = curl_exec($curl);

        // $url = "https://api.accesstrade.vn/v1/offers_informations/merchant_list";
        // $response = file_get_contents($url);
        // echo $response;
        $json_data = json_decode($response, true);
        if ( $json_data['data'] ){
            $data = $json_data['data'];
    
            foreach ($data as $merchant) {
                $fix_merchant_params = array(
                    'display_name' => $merchant['display_name'][0],
                    'merchant_key' => $merchant['id'],
                    'login_name' => $merchant['login_name'],
                    'logo' => $merchant['logo'],
                    'total_offer' => $merchant['total_offer'],
                   
                );
                // echo json_encode($fix_merchant_params);
                $this->merchant_list_model->save_merchant_list($fix_merchant_params);
            }
        }


      
    }

    public function import_vouchers() {
        $this->load->model('merchant_list_model');
        $this->load->model('icontext_list_model');
        $this->load->model('coupons_model');
        $this->load->model('banner_model');
        $this->load->model('category_model');
        // $merchant_list_model = new Merchant_list_model();
        // $icontext_list_model = new Icontext_list_model();
        // $coupon_model = new Coupon_model();
        $merchant_list = $this->merchant_list_model->get_merchant_list();
        // echo json_encode($merchant_list);
        // return;
        foreach ($merchant_list as $merchant) {
            $merchant_id = $merchant['merchant_key'];
            // $url = "https://api.accesstrade.vn/v1/offers_informations/icontext_list?merchant=" . $merchant_id;
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => "https://api.accesstrade.vn/v1/offers_informations/icontext_list?merchant=$merchant_id",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTPHEADER => ['Authorization: Token RWdYZRWaB7vR3lSrwqYAzmEn-h7dvt6-'],
            ]);

            $response = curl_exec($curl);
           
            // $response = file_get_contents($url);
            $json_data = json_decode($response, true);
            if (!$json_data['data']) continue;
            $data = $json_data['data'];
            $icon_texts = array();
            foreach ($data as $icontext) {
                $icon_text = $icontext['icon_text'];
                array_push($icon_texts, $icon_text);
            }
            $coupons = array();
            foreach ($icon_texts as $icon_text) {
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://api.accesstrade.vn/v1/offers_informations/coupon?icon_text=' . urlencode($icon_text).'&limit=100',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTPHEADER => array(
                        'Authorization: Token RWdYZRWaB7vR3lSrwqYAzmEn-h7dvt6-'
                    ),
                ));
                $response = curl_exec($curl);
                // echo $response;
                $json_coupon = json_decode($response, true);
                if (!$json_coupon['data'] || $json_coupon['count'] == 0) continue;
                $data = $json_coupon['data'];
                // $data['coupon_key'] = $data['id'];
                $coupons = array_merge($coupons, $data);
            }

           
            foreach ($coupons as $coupon) {
            //    echo $coupon['id']; continue;
                $coupon_data = array(
                    'coupon_key' => $coupon['id'],
                    'coupon_code' => $coupon['coupons'][0]['coupon_code'],
                    'coupon_desc' => $coupon['coupons'][0]['coupon_desc'],
                    'content' => $coupon['content'],
                    'image' => $coupon['image'],
                    'link' => $coupon['link'],
                    'merchant' => $merchant_id,
                    'name' => $coupon['name'],
                    'start_time' => date('Y-m-d H:i:s', strtotime($coupon['start_time'])),
                    'end_time' => date('Y-m-d H:i:s', strtotime($coupon['end_time'])),
                    'domain' => $coupon['domain'],
                    'prod_link' => $coupon['aff_link']
                );

                $start_time = DateTime::createFromFormat('Y/m/d H:i', $coupon['start_time']);
                $coupon_data['start_time'] = $start_time->format('Y-m-d H:i:s');
                $coupon_id = $this->coupons_model->save_coupon($coupon_data);
                continue;
                $banners = $coupon['banners'];
                foreach ($banners as $banner) {
                    $banner_id = $this->banner_model->insert_banner($coupon_id, $banner);
                }
                $categories = $coupon['categories'];
                foreach ($categories as $category) {
                    $this->category_model->save_category($coupon_id, $category);
                }
                $details = $coupon['coupons'];
                $details['coupon_id'] = $coupon_id;
                $this->coupons_model->save_coupons($details);
            }
        }
    }

    // Đơn hàng BHX
    public function orders_crawl_bhx(){
        $data = $this->input->post('data');
        if (!$data) {
            $data = $this->input->raw_input_stream;
        }
        $normalize_item = function ($item) {
            $normalized = [];
            foreach ((array)$item as $key => $value) {
                $clean_key = $key;
                if (is_string($clean_key)) {
                    $clean_key = str_replace("\xEF\xBB\xBF", '', $clean_key);
                }
                if (is_string($value)) {
                    $value = trim($value);
                }
                $normalized[$clean_key] = $value;
            }
            return $normalized;
        };
        $no_save = $this->input->get('no_save');
        $no_save = filter_var($no_save, FILTER_VALIDATE_BOOLEAN);
        $this->load->model('external_model');
        $json_str = json_decode($data, true);
        if ($json_str && isset($json_str[0]) && is_array($json_str[0]) && count($json_str) === 1) {
            $json_str = $json_str;
        } elseif ($json_str && isset($json_str['ORDERID'])) {
            $json_str = array($json_str);
        } elseif ($json_str && isset($json_str["\xEF\xBB\xBFORDERID"])) {
            $item = $json_str;
            $item['ORDERID'] = $item["\xEF\xBB\xBFORDERID"];
            unset($item["\xEF\xBB\xBFORDERID"]);
            $json_str = array($item);
        }
        if ($json_str && is_array($json_str)) {
            $json_str = array_map($normalize_item, $json_str);
        }
      
        if ($json_str && count($json_str)>0){
              //Tách đơn theo PO
            // Assuming your data is stored in an array called $data
            $groupedData = array();
            
            foreach ($json_str as $item) {
                $item = $normalize_item($item);
                if (isset($item["\xEF\xBB\xBFORDERID"]) && !isset($item['ORDERID'])) {
                    $item['ORDERID'] = $item["\xEF\xBB\xBFORDERID"];
                    unset($item["\xEF\xBB\xBFORDERID"]);
                }
                $orderId = trim($item['ORDERID']);

                // If the current order ID doesn't exist in the grouped data array, add it
                if (!isset($groupedData[$orderId])) {
                    $groupedData[$orderId] = array();
                }

                // Add the current item to the group for the current order ID
                $groupedData[$orderId][] = $item;
            }

            //  Lấy danh sách đơn hàng để kiểm tra và tạo PO
            $processed_orders = [];
            foreach ($groupedData as $order_id => $order){
                $uniquekey = "BHX_".$order_id;
                $check_order = $no_save ? false : $this->external_model->get_record($uniquekey);
                if ( !$check_order ){
                    // Đơn hàng chưa tạo
                    $result = true;
                    if (!$no_save) {
                        $result = $this->external_model->add_record(array(
                            "uniquekey" => $uniquekey,
                            "rel" => 'Order',
                            "root_id" => $order_id,
                            "target_id" => false
                        ));
                    }
            
                    if ( $result ){
                        $masked_items = array();
                        // Xử lý sản phẩm:
                        $SHIPTOSTORENAME = "";
                        foreach ($order as $item){
                            $m_order_item = $item;
                            $sku = str_replace('"', '', $item['BARCODE']);
                            
                            $result = $this->external_model->get_sku_from_barcode_v2($sku,"fast_barco");
                            // // echo '123';
                            // echo json_encode( array(
                            //     'result' => $result,
                            //     'sku' => $sku
                            // ) );
                            if ($result){  
                                $m_order_item['BARCODE'] = $result['sku'];
                                // $masked_items[] = $result;
                                // $data[$mpos] = 
                                // echo json_encode( $result );
                            }
                            $masked_items[] = $m_order_item;
                            if ( !$SHIPTOSTORENAME ){
                                $SHIPTOSTORENAME  = $item["SHIPTOSTORENAME"];
                            }
                        }
                        array_push($processed_orders , array(
                            'order' => array(
                                "id" => $no_save ? false : $result,
                                "uniquekey" => $uniquekey,
                                "rel" => 'Order',
                                "root_id" => $order_id,
                                "target_id" => false,
                                "SHIPTOSTORENAME" => $SHIPTOSTORENAME
                            ),
                            'items' => $masked_items,
                            // 'old_items' => $order
                        ));
                        
                        
                    }
                }
            }
            echo json_encode( $processed_orders );
        }
        // $uniquekey = $ponumber.$poId;
        // $order = $this->external_model->get_record($uniquekey);


        die();
    }

    // Đơn hàng E-mart
    public function orders_crawl_emart(){
        if (file_exists(APPPATH . 'libraries/App_simple_html_dom.php')) {
            // array_push($autoload['helper'], 'my_functions');
            include(APPPATH . 'libraries/App_simple_html_dom.php');
        }
        $this->load->model('external_model');
  
        $xml = $this->input->post('data');
        $dom = new DOMDocument;
        @$dom->loadHTML(mb_convert_encoding($xml, 'HTML-ENTITIES', 'UTF-8'));

        // Create a new XPath instance
        $xpath = new DOMXPath($dom);
        // Get all tr elements in the table
        $rows = $xpath->query('//tr');

        // Đầu tiên cần tìm vị trí chưa hàng Page, bên dưới sẽ có danh sách sản phẩm
        $position = 0;
        // Iterate over each row
        foreach ($rows as $i => $row) {
            // Get all td elements in this row
            $cols = $xpath->query('td', $row);

            // Iterate over each column
            foreach ($cols as $col) {
                // If the column contains the text "Page", print the position (index + 1) and break the loop
                if (strpos($col->nodeValue, 'Page') !== false) {
                    $position  = ($i + 1) ;
                    break 2;
                }
            }
        }

         // Vị trí cuối
         $lastPosition = 0;
         // Iterate over each row
         foreach ($rows as $i => $row) {
             // Get all td elements in this row
             $cols = $xpath->query('td', $row);
 
             // Iterate over each column
             foreach ($cols as $col) {
                 // If the column contains the text "Page", print the position (index + 1) and break the loop
                 if (strpos($col->nodeValue, 'Total Amount(without VAT)') !== false) {
                    $lastPosition  = ($i) ;
                    break 2;
                 }
             }
        }

        // PO
        $PONumber = "";
        // Iterate over each row
        foreach ($rows as $i => $row) {
            // Get all td elements in this row
            $cols = $xpath->query('td', $row);
        
            // Iterate over each column
            foreach ($cols as $j => $col) {
                // If the column contains the text "PO No.", get the order number and break the loop
                if (strpos($col->nodeValue, 'PO No.') !== false) {
                    // Order number is in the next column (index $j + 2)
                    if ($cols->length > $j + 2) {
                        $PONumber = trim($cols->item($j + 2)->nodeValue);
                    }
                    break 2;
                }
            }
        }

         // PO
         $Delivery_to = "";
         // Iterate over each row
         foreach ($rows as $i => $row) {
             // Get all td elements in this row
             $cols = $xpath->query('td', $row);
         
             // Iterate over each column
             foreach ($cols as $j => $col) {
                 // If the column contains the text "PO No.", get the order number and break the loop
                 if (strpos($col->nodeValue, 'Delivery to') !== false) {
                     // Order number is in the next column (index $j + 2)
                     if ($cols->length > $j + 2) {
                         $Delivery_to = trim($cols->item($j + 2)->nodeValue);
                     }
                     break 2;
                 }
             }
         }
        
        // headers của Emarts
        $headers = [];
        if ($rows->length > $position ) {
            $row = $rows->item($position );
            
            // Get all td elements in this row
            $cols = $xpath->query('td', $row);
            // Extract the data from the td elements and add it to an array
           
            foreach ($cols as $col) {
                $value = $col->nodeValue;
                $value = mb_convert_encoding($value, 'UTF-8');
                $headers[] = $value;
            }
        }

        // Đọc qua danh sách sản phẩm
        $products = [];
       // If both the start and end positions were found
        if ($position !== 0 && $lastPosition !== 0) {
            // Process the product rows between the start and end positions
            for ($i = $position+1; $i < $lastPosition; $i++) {
                $row = $rows->item($i);
                // Get all td elements in this row
                $cols = $xpath->query('td', $row);
                $data = [];
                foreach ($cols as $j => $col) {
                    // Use the corresponding header as the key
                    if (isset($headers[$j])) {
                        $data[$headers[$j]] = $col->nodeValue;
                    }
                    // $products[] = $data;
                    // array_push($data, $products );
                }
                $products[] = $data;
            }
        }

        $uniquekey = "EMART_PO_".$PONumber;
        $check_order = $this->external_model->get_record($uniquekey);
        $processed_order = [];
        if ( !$check_order ){
            // Đơn hàng chưa tạo
            $qresult = $this->external_model->add_record(array(
                "uniquekey" => $uniquekey,
                "rel" => 'Order',
                "root_id" => $PONumber,
                "target_id" => false
            ));
    
            if ( $qresult ){
                $masked_items = array();
                // Xử lý sản phẩm:
                foreach ($products as $item){
                    $m_order_item = $item;
                    $sku = str_replace('"', '', $item['Unit Barcode(Mã vạch lẻ)']);
                    
                    $result = $this->external_model->get_sku_from_barcode_v2($sku,"fast_barco");
                    if ($result){  
                        $m_order_item['BARCODE'] = $result['sku'];
                    } else {
                        $seach_emart = $this->external_model->get_sku_from_barcode_v2($sku,"emart_sku");
                        if ($seach_emart){  
                            $m_order_item['BARCODE'] = $seach_emart['sku'];
                        }
                    }
                    $m_order_item['PRODUCTNAME'] = $item['Unit Barcode Description(Tên sản phẩm lẻ)'];
                    $m_order_item['PRICE'] = str_replace('.', '',$item['Pur. Price(-VAT)']);
                    $m_order_item['QUANTITY'] = $item['PO Qty.'];

                    if (!empty( $item['Unit Barcode(Mã vạch lẻ)'] ) && !empty($item['PO Qty.'])){
                        $masked_items[] = $m_order_item;
                    }
                  
                   
                }

                $processed_order = array(
                    'order' => array(
                        "id" => $qresult,
                        "uniquekey" => $uniquekey,
                        "rel" => 'Order',
                        "root_id" => $PONumber,
                        "target_id" => false,
                        'Delivery_to' => $Delivery_to
                    ),
                    'items' => $masked_items,
                    // 'old_items' => $order
                );
            }
        }

        echo json_encode( array(
            'processed_order' =>  $processed_order, 
            'output' =>  $products, 
            'headers' =>  $headers, 
            'firstPosition' =>  $position, 
            'lastPosition' =>  $lastPosition, 
            'PONumber' =>  $PONumber, 
            "post" => $xml,
           
        ));
        

        // $html = str_get_html($data);
        // Find the first table in the HTML

        
        // $table = $html->find('table');
        // echo json_encode( array(
        //     'data' => $table
        // ));
        return;
        
        die();
    }

    public function orders_crawl_emart_v2(){
        if (file_exists(APPPATH . 'libraries/App_simple_html_dom.php')) {
            include(APPPATH . 'libraries/App_simple_html_dom.php');
        }
        $this->load->model('external_model');
      
        $xml = $this->input->post('data');
        
        // Sử dụng simple_html_dom để lấy nội dung thẻ <p> đầu tiên
        $html = str_get_html($xml);
        $headerText = '';
        if($html){
            $p = $html->find('p', 0);
            if($p){
                $headerText = $p->plaintext;
            }
        }
        
        // Trích xuất PO No. và Delivery to bằng regex (không phân biệt chữ hoa thường)
        $PONumber = "";
        $Delivery_to = "";
        if (preg_match('/PO No\. ?:\s*([\d]+)/i', $headerText, $matches)) {
            $PONumber = trim($matches[1]);
        }
        if (preg_match('/Delivery to ?:\s*(.+?)(?=\s*(Delivery Date|VAT|$))/is', $headerText, $matches)) {
            $Delivery_to = trim($matches[1]);
        }
        
        // Sử dụng DOMDocument và XPath để xử lý bảng sản phẩm
        $required_headers = ['ITEM NO. DESCRIPTION', 'QTY', 'UNIT PRICE'];

        $dom = new DOMDocument;
        @$dom->loadHTML(mb_convert_encoding($xml, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);
    
        // Tìm bảng chứa "Article Code" trong hàng đầu tiên
        $productTable = null;
        $tables = $xpath->query('//table');
        foreach ($tables as $table) {
            $firstRow = $xpath->query('.//tr[1]', $table)->item(0);
            $firstCell = $xpath->query('.//td[1]', $firstRow)->item(0);
            if ($firstCell && trim($firstCell->nodeValue) === 'Article Code') {
                $productTable = $table;
                break;
            }
        }
        
        // Xử lý dữ liệu bảng sản phẩm
        if ($productTable) {
            $headers = [];
            $headerRow = $xpath->query('.//tr[1]', $productTable)->item(0);
            $headerCols = $xpath->query('.//td', $headerRow);
            foreach ($headerCols as $col) {
                $headers[] = trim($col->nodeValue);
            }
        
            $products = [];
            $productRows = $xpath->query('.//tr[position()>1]', $productTable);
            foreach ($productRows as $row) {
                $cols = $xpath->query('.//td', $row);
                $data = [];
                foreach ($cols as $j => $col) {
                    if (isset($headers[$j])) {
                        $data[$headers[$j]] = trim($col->nodeValue);
                    }
                }
                $products[] = $data;
            }
        } else {
            $products = [];
            $headers = [];
        }
        
        // Xử lý đơn hàng và sản phẩm theo logic gốc
        $uniquekey = "EMART_PO_" . $PONumber;
        $check_order = $this->external_model->get_record($uniquekey);
        $processed_order = [];
        if (!$check_order) {
            $qresult = $this->external_model->add_record(array(
                "uniquekey" => $uniquekey,
                "rel" => 'Order',
                "root_id" => $PONumber,
                "target_id" => false
            ));
        
            if ($qresult) {
                $masked_items = [];
                $error_masked_items = [];
        
                // Định nghĩa form header mong muốn (sau khi normalize)
                $expected_headers = [
                    "Article Code",
                    "Unit Barcode (Mã vạch lẻ)",
                    "Unit Barcode Description (Tên sản phẩm lẻ)",
                    "PO Unit",
                    "Qty. inBox",
                    "PO Qty.",
                    "Pur. Price (-VAT)",
                    "Amount (-VAT)",
                    "Free PO"
                ];
        
                foreach ($products as $item) {
                    // Chuẩn hóa các key: loại bỏ xuống dòng và hợp nhất khoảng trắng
                    $normItem = [];
                    foreach ($item as $key => $value) {
                        $normalizedKey = preg_replace('/\s+/', ' ', trim(str_replace("\n", ' ', $key)));
                        $normItem[$normalizedKey] = $value;
                    }
        
                    // Chỉ xử lý item có đủ các key theo expected_headers
                    $hasExpectedHeaders = true;
                    foreach ($expected_headers as $header) {
                        if (!isset($normItem[$header])) {
                            $hasExpectedHeaders = false;
                            break;
                        }
                    }
                    if (!$hasExpectedHeaders) {
                        $error_masked_items[] = $normItem;
                        continue;
                    }
        
                    // Lấy SKU từ "Article Code" theo form header
                    
                    $sku = trim($normItem["Unit Barcode (Mã vạch lẻ)"]);
                    $sku = str_replace('"', '', $sku);
        
                    // Lấy giá và số lượng
                    $netPriceCol = $normItem["Pur. Price (-VAT)"];
                    $poQty      = $normItem["PO Qty."];
                    $quantity   = floatval($poQty);
        
                    $search_emart = $this->external_model->get_sku_from_barcode_v2($sku, "emart_sku");
                    if ($search_emart) {  
                        $normItem['BARCODE'] = $search_emart['sku'];
                    } else {
                        // Tìm thông tin SKU từ external_model (ưu tiên fast_barco)
                        $result = $this->external_model->get_sku_from_barcode_v2($sku, "fast_barco");
                        if ($result) {  
                            $normItem['BARCODE'] = $result['sku'];
                        } else {
                             $normItem['BARCODE'] = $sku;
                        }
                    }
                   
        
                    // Lấy tên sản phẩm từ "Unit Barcode Description (Tên sản phẩm lẻ)"
                    $normItem['PRODUCTNAME'] = trim($normItem["Unit Barcode Description (Tên sản phẩm lẻ)"]);
                    // Xử lý giá: loại bỏ dấu chấm nếu có
                    $normItem['PRICE'] = str_replace('.', '', $netPriceCol);
                    $normItem['QUANTITY'] = $quantity;
        
                    if ($sku !== '' && $quantity > 0) {
                        $masked_items[] = $normItem;
                    } else {
                        $error_masked_items[] = $normItem;
                    }
                }
        
                $processed_order = [
                    'order' => [
                        "id"        => $qresult,
                        "uniquekey" => $uniquekey,
                        "rel"       => 'Order',
                        "root_id"   => $PONumber,
                        "target_id" => false,
                        // Có thể thêm Delivery_to nếu cần (ví dụ trích từ header ngoài bảng)
                    ],
                    'items' => $masked_items,
                ];
            }
        }
        
        echo json_encode(array(
            'processed_order' => $processed_order, 
            'output' => $products, 
            'headers' => $headers, 
            'PONumber' => $PONumber, 
            "post" => $xml,
        ));
    }
    
    
    
    public function orders_crawl_kohnan(){
        if (file_exists(APPPATH . 'libraries/App_simple_html_dom.php')) {
            include(APPPATH . 'libraries/App_simple_html_dom.php');
        }
        $this->load->model('external_model');

        $xml = $this->input->post('data');

        $html = str_get_html($xml);
        $headerText = '';
        $footerText = '';
        if ($html) {
            $paragraphs = $html->find('p');
            if (isset($paragraphs[0])) {
                $headerText = $paragraphs[0]->plaintext;
            }
            if (isset($paragraphs[1])) {
                $footerText = $paragraphs[1]->plaintext;
            }
        }

        $PONumber = '';
        if (preg_match('/PURCHASE ORDER NO\.?\s*([0-9]+)/i', $headerText, $matches)) {
            $PONumber = trim($matches[1]);
        }

        $deliveryDate = '';
        if (preg_match('/DELIVERY DATE\s*([0-9\/]+)/i', $headerText, $matches)) {
            $deliveryDate = trim($matches[1]);
        }

        $shipToName = '';
        $shipBlock = '';
        if ($headerText && preg_match('/SHIP TO\s*(.+?)(?:Phone|Fax|DELIVERY DATE|$)/is', $headerText, $matches)) {
            $shipBlock = trim($matches[1]);
            $shipLines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $shipBlock))));
            foreach ($shipLines as $line) {
                if ($line === '') {
                    continue;
                }
                if (stripos($line, 'KOHNAN') !== false) {
                    $shipToName = $line;
                    break;
                }
            }
            if ($shipToName === '' && !empty($shipLines)) {
                $shipToName = $shipLines[0];
            }
        }

        if ($shipBlock !== '') {
            if (preg_match('/(KOHNAN\s+JAPAN\s*[\x{2013}-]\s*CỬA\s+HÀNG[^\n]*?)(?:\s+L[oô]\b|\s+Phone|\s+Fax|$)/u', $shipBlock, $nameMatches)) {
                $shipToName = trim($nameMatches[1]);
            } elseif (preg_match('/(KOHNAN\s+JAPAN[^\n]*)/u', $shipBlock, $nameMatches)) {
                $shipToName = trim($nameMatches[1]);
            }
        }

        $pageTotal = '';
        if ($footerText && preg_match('/PAGE TOTAL\s*([0-9\.,]+)/i', $footerText, $matches)) {
            $pageTotal = trim($matches[1]);
        }

        $grandTotal = '';
        if ($footerText && preg_match('/GRAND TOTAL\s*([0-9\.,]+)/i', $footerText, $matches)) {
            $grandTotal = trim($matches[1]);
        }

        $kohnanStoreMappings = [
            'ST000315' => [
                ['buyer_code' => 'NM03353', 'aliases' => [
                    'KOHNAN JAPAN - AEON MALL BINH TAN',
                    'KOHNAN JAPAN - CUA HANG BINH TAN',
                    'KOHNAN JAPAN - AEON BINH TAN',
                    'KOHNAN JAPAN - BINH TAN'
                ]],
                ['buyer_code' => 'NM03354', 'aliases' => [
                    'KOHNAN JAPAN - GIGA MALL',
                    'KOHNAN JAPAN - GIGA'
                ]],
                ['buyer_code' => 'NM03355', 'aliases' => [
                    'KOHNAN JAPAN - TAN PHU CELADON',
                    'KOHNAN JAPAN - CUA HANG TAI AEON TAN PHU CELADON',
                    'KOHNAN JAPAN - TAN PHU'
                ]],
                ['buyer_code' => 'NM06185', 'aliases' => [
                    'KOHNAN JAPAN - PARKSON LE THANH TON',
                    'KOHNAN JAPAN - LE THANH TON'
                ]],
                ['buyer_code' => 'NM07016', 'aliases' => [
                    'KOHNAN JAPAN - NGUYEN THI THAP'
                ]],
                ['buyer_code' => 'NM07948', 'aliases' => [
                    'KOHNAN JAPAN - VINCOM LE VAN VIET',
                    'KOHNAN JAPAN - LE VAN VIET'
                ]],
                ['buyer_code' => 'NM07960', 'aliases' => [
                    'KOHNAN JAPAN - ORIENTIAL PLAZA',
                    'KOHNAN JAPAN - ORIENTAL PLAZA'
                ]],
                ['buyer_code' => 'NM08081', 'aliases' => [
                    'KOHNAN JAPAN - PARC MALL QUAN 8',
                    'KOHNAN JAPAN - PARC MALL Q8'
                ]],
            ],
            'ST000327' => [
                ['buyer_code' => 'NM05661', 'aliases' => [
                    'KOHNAN JAPAN - AEON MALL BINH DUONG CANARY',
                    'KOHNAN JAPAN - BINH DUONG CANARY'
                ]],
                ['buyer_code' => 'NM07311', 'aliases' => [
                    'KOHNAN JAPAN - SORA GARDENS',
                    'KOHNAN JAPAN - SORA GARDEN'
                ]],
            ],
            'ST000355' => [
                ['buyer_code' => 'NM00598', 'aliases' => [
                    'KOHNAN JAPAN - OCEAN CITY'
                ]],
                ['buyer_code' => 'NM05733', 'aliases' => [
                    'KOHNAN JAPAN - AEON MALL HA DONG',
                    'KOHNAN JAPAN - HA DONG'
                ]],
                ['buyer_code' => 'NM07254', 'aliases' => [
                    'KOHNAN JAPAN - VINCOM NGUYEN CHI THANH',
                    'KOHNAN JAPAN - NGUYEN CHI THANH'
                ]],
                ['buyer_code' => 'NM08311', 'aliases' => [
                    'KOHNAN JAPAN - ROYAL CITY'
                ]],
            ],
            'ST000401' => [
                ['buyer_code' => 'NM06207', 'aliases' => [
                    'KOHNAN JAPAN - AEON MALL HAI PHONG',
                    'KOHNAN JAPAN - AEON MALL HAI PHONG LE CHAN',
                    'KOHNAN JAPAN - HAI PHONG'
                ]],
            ],
            'ST000402' => [
                ['buyer_code' => 'NM06199', 'aliases' => [
                    'KOHNAN JAPAN - VINCOM BIEN HOA',
                    'KOHNAN JAPAN - BIEN HOA'
                ]],
            ],
            'ST000449' => [
                ['buyer_code' => 'NM06938', 'aliases' => [
                    'KOHNAN JAPAN - VINCOM SMART CITY',
                    'KOHNAN JAPAN - SMART CITY'
                ]],
            ],
            'ST000451' => [
                ['buyer_code' => 'NM06966', 'aliases' => [
                    'KOHNAN JAPAN - AEON MALL LONG BIEN',
                    'KOHNAN JAPAN - LONG BIEN'
                ]],
            ],
        ];

        $normalizeText = function ($value) {
            $value = str_replace(['–', '—', '−'], '-', $value);
            $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($transliterated !== false) {
                $value = $transliterated;
            }
            $value = strtoupper($value);
            $value = preg_replace('/[^A-Z0-9\-\s]/', ' ', $value);
            $value = preg_replace('/\s+/', ' ', $value);
            return trim($value);
        };

        $matchedCustomerCode = '';
        $matchedBuyerCode = '';
        $shipCompareSource = $shipToName !== '' ? $shipToName : $shipBlock;
        if ($shipCompareSource !== '') {
            $normalizedShip = $normalizeText($shipCompareSource);
            $normalizedShip = str_replace('-', ' ', $normalizedShip);
            $normalizedShip = preg_replace('/\s+/', ' ', $normalizedShip);

            $stopWords = ['KOHNAN', 'JAPAN', 'CUA', 'HANG', 'TAI', 'SHOP', 'STORE', 'TIEM'];

            $tokenize = function ($value) use ($stopWords) {
                $value = str_replace('-', ' ', $value);
                $value = preg_replace('/\s+/', ' ', $value);
                $tokens = explode(' ', $value);
                $filtered = [];
                foreach ($tokens as $token) {
                    $token = trim($token);
                    if ($token === '') {
                        continue;
                    }
                    if (in_array($token, $stopWords, true)) {
                        continue;
                    }
                    if (strlen($token) < 2) {
                        continue;
                    }
                    $filtered[] = $token;
                }
                return $filtered;
            };

            foreach ($kohnanStoreMappings as $customerCode => $buyers) {
                foreach ($buyers as $buyer) {
                    foreach ($buyer['aliases'] as $alias) {
                        $normalizedAlias = $normalizeText($alias);
                        if ($normalizedAlias === '') {
                            continue;
                        }
                        $aliasTokens = $tokenize($normalizedAlias);
                        if (empty($aliasTokens)) {
                            continue;
                        }

                        $allMatch = true;
                        foreach ($aliasTokens as $token) {
                            if (strpos($normalizedShip, $token) === false) {
                                $allMatch = false;
                                break;
                            }
                        }

                        if ($allMatch) {
                            $matchedCustomerCode = $customerCode;
                            $matchedBuyerCode = $buyer['buyer_code'];
                            break 3;
                        }
                    }
                }
            }
        }

        $dom = new DOMDocument;
        @$dom->loadHTML(mb_convert_encoding($xml, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);

        $productTable = null;
        $tables = $xpath->query('//table');
        foreach ($tables as $table) {
            $firstRow = $xpath->query('.//tr[1]', $table)->item(0);
            if (!$firstRow) {
                continue;
            }
            $firstCell = $xpath->query('.//th|.//td', $firstRow)->item(0);
            if ($firstCell) {
                $firstText = trim(preg_replace('/\s+/', ' ', str_replace("\xc2\xa0", ' ', $firstCell->nodeValue)));
                if (preg_match('/^NO\.?$/i', $firstText)) {
                    $productTable = $table;
                    break;
                }
            }
        }

        if ($productTable) {
            $headers = [];
            $normalizedHeaders = [];
            $headerRow = $xpath->query('.//tr[1]', $productTable)->item(0);
            $headerCols = $xpath->query('.//th|.//td', $headerRow);
            foreach ($headerCols as $col) {
                $rawHeader = trim($col->nodeValue);
                $headers[] = $rawHeader;
                $normalizedHeaders[] = preg_replace('/\s+/', ' ', str_replace("\n", ' ', $rawHeader));
            }

            $products = [];
            $productRows = $xpath->query('.//tr[position()>1]', $productTable);
            foreach ($productRows as $row) {
                $cols = $xpath->query('.//td', $row);
                $data = [];
                $hasValue = false;
                foreach ($cols as $j => $col) {
                    if (isset($normalizedHeaders[$j])) {
                        $value = trim($col->nodeValue);
                        $data[$normalizedHeaders[$j]] = $value;
                        if ($value !== '') {
                            $hasValue = true;
                        }
                    }
                }
                if ($hasValue) {
                    $products[] = $data;
                }
            }

            $headers = $normalizedHeaders;
        } else {
            $headers = [];
            $products = [];
        }

        if (!empty($products)) {
            $filteredProducts = [];
            foreach ($products as $item) {
                $normItem = [];
                foreach ($item as $key => $value) {
                    $normalizedKey = preg_replace('/\s+/', ' ', trim(str_replace("\n", ' ', $key)));
                    $normItem[$normalizedKey] = trim($value);
                }

                $hasRequired = true;
                foreach ($required_headers as $header) {
                    if (!isset($normItem[$header]) || $normItem[$header] === '') {
                        $hasRequired = false;
                        break;
                    }
                }

                if ($hasRequired) {
                    $filteredProducts[] = $normItem;
                }
            }

            $products = $filteredProducts;
        }

        $uniquekey = 'KOHNAN_PO_' . $PONumber;
        $check_order = $this->external_model->get_record($uniquekey);
        $processed_order = [];
        if (!$check_order) {
            $qresult = $this->external_model->add_record([
                'uniquekey' => $uniquekey,
                'rel' => 'Order',
                'root_id' => $PONumber,
                'target_id' => false,
            ]);
            if ($qresult) {
                $masked_items = [];
                $error_masked_items = [];

                foreach ($products as $item) {
                    $normItem = [];
                    foreach ($item as $key => $value) {
                        $normalizedKey = preg_replace('/\s+/', ' ', trim(str_replace("\n", ' ', $key)));
                        $normItem[$normalizedKey] = trim($value);
                    }

                    $hasRequired = true;
                    foreach ($required_headers as $header) {
                        if (!isset($normItem[$header]) || $normItem[$header] === '') {
                            $hasRequired = false;
                            break;
                        }
                    }
                    if (!$hasRequired) {
                        $error_masked_items[] = $normItem;
                        continue;
                    }

                    $descParts = preg_split('/\r\n|\r|\n/', $normItem['ITEM NO. DESCRIPTION']);
                    $descParts = array_values(array_filter(array_map('trim', $descParts), function ($part) {
                        return $part !== '';
                    }));

                    $candidateBarcode = $descParts[0] ?? '';
                    $productName = '';
                    if (count($descParts) > 1) {
                        $productName = implode(' ', array_slice($descParts, 1));
                    } else {
                        $productName = $normItem['ITEM NO. DESCRIPTION'];
                    }

                    if ($productName === '' && isset($normItem['MODEL BRAND'])) {
                        $productName = $normItem['MODEL BRAND'];
                    }

                    $sku = preg_replace('/[^0-9A-Za-z]/', '', $candidateBarcode);
                    if ($sku === '' && isset($normItem['MODEL BRAND'])) {
                        $sku = preg_replace('/\s+/', '', $normItem['MODEL BRAND']);
                    }

                    $quantityRaw = str_replace(',', '', $normItem['QTY']);
                    $quantity = (float) $quantityRaw;

                    $priceRaw = str_replace(',', '', $normItem['UNIT PRICE']);
                    $priceNumeric = preg_replace('/[^0-9.]/', '', $priceRaw);

                    if ($sku !== '') {
                        $searchResult = $this->external_model->get_sku_from_barcode_v2($sku, 'fast_barco');
                        if ($searchResult) {
                            $normItem['BARCODE'] = $searchResult['sku'];
                        } else {
                            $normItem['BARCODE'] = $sku;
                        }
                    } else {
                        $normItem['BARCODE'] = $sku;
                    }

                    $normItem['PRODUCTNAME'] = $productName;
                    $normItem['PRICE'] = $priceNumeric;
                    $normItem['QUANTITY'] = $quantity;

                    if ($normItem['BARCODE'] !== '' && $quantity > 0) {
                        $masked_items[] = $normItem;
                    } else {
                        $error_masked_items[] = $normItem;
                    }
                }

                $processed_order = [
                    'order' => [
                        'id' => $qresult,
                        'uniquekey' => $uniquekey,
                        'rel' => 'Order',
                        'root_id' => $PONumber,
                        'target_id' => false,
                        'DeliveryDate' => $deliveryDate,
                        'ShipToName' => $shipToName,
                        'PageTotal' => $pageTotal,
                        'GrandTotal' => $grandTotal,
                        'CustomerCode' => $matchedCustomerCode,
                        'BuyerCode' => $matchedBuyerCode,
                    ],
                    'items' => $masked_items,
                ];
            }
        }

        echo json_encode([
            'processed_order' => $processed_order,
            'output' => $products,
            'headers' => $headers,
            'PONumber' => $PONumber,
            'DeliveryDate' => $deliveryDate,
            'ShipToName' => $shipToName,
            'PageTotal' => $pageTotal,
            'GrandTotal' => $grandTotal,
            'CustomerCode' => $matchedCustomerCode,
            'BuyerCode' => $matchedBuyerCode,
            'post' => $xml,
        ]);
    }
    
    
    public function orders_crawl_winmart(){
        $this->load->model('external_model');

        $raw = $this->input->post('data');
        $payload = json_decode($raw, true);

        if ($payload === null && json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode([
                'error' => 'INVALID_JSON',
                'message' => json_last_error_msg(),
                'post' => $raw,
            ]);
            return;
        }

        $orderId = $payload['orderId'] ?? '';
        $orderUrl = $payload['orderUrl'] ?? '';
        $shippingInfo = $payload['shippingInfo'] ?? [];
        $supplierInfo = $payload['supplierInfo'] ?? [];
        $items = $payload['items'] ?? [];

        $deliveryDate = $shippingInfo['deliveryDate'] ?? '';
        $orderDate = $shippingInfo['orderDate'] ?? '';
        $orderGroup = $shippingInfo['orderGroup'] ?? '';
        $shipDestinationRaw = $shippingInfo['destination'] ?? '';
        $shipDestinationFull = $shippingInfo['destinationFull'] ?? '';
        $shipAddress = $shippingInfo['address'] ?? '';

        $shipToName = '';
        if ($shipDestinationRaw !== '') {
            $parts = array_map('trim', explode('-', $shipDestinationRaw));
            if (count($parts) > 1) {
                $shipToName = trim(end($parts));
            } else {
                $shipToName = trim($shipDestinationRaw);
            }
            if ($shipToName !== '') {
                $shipToName = preg_replace('/_+/', ' ', $shipToName);
            }
        }

        $normalizeAddress = function ($value) {
            $value = str_replace(['–', '—', '−', '_'], ' ', $value);
            $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($transliterated !== false) {
                $value = $transliterated;
            }
            $value = strtoupper($value);
            $value = preg_replace('/[^A-Z0-9\s]/', ' ', $value);
            $value = preg_replace('/\s+/', ' ', $value);
            return trim($value);
        };

        $addressComponents = trim(implode(' ', array_filter([
            $shipAddress,
            $shipDestinationFull,
            $shipDestinationRaw,
        ], function ($val) {
            return $val !== null && $val !== '';
        })));

        $normalizedAddress = $normalizeAddress($addressComponents);
        $addressTokens = array_filter(explode(' ', $normalizedAddress));
        $addressTokenSet = [];
        foreach ($addressTokens as $token) {
            $addressTokenSet[$token] = true;
            $strippedToken = preg_replace('/\d+/', '', $token);
            if ($strippedToken !== '' && $strippedToken !== $token) {
                $addressTokenSet[$strippedToken] = true;
            }
        }

        $headers = [
            'index',
            'productCode',
            'productName',
            'barcode',
            'unit',
            'price',
            'orderedQuantity',
            'scheduledQuantity',
            'note1',
            'note2',
        ];

        $products = [];
        $masked_items = [];
        $totalAmount = 0;

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $normalized = [];
            foreach ($headers as $header) {
                $normalized[$header] = $item[$header] ?? '';
            }

            $products[] = $normalized;

            $barcode = trim((string)($item['barcode'] ?? ''));
            $price = (float)($item['price'] ?? 0);

            $scheduledQty = isset($item['scheduledQuantity']) ? (float)$item['scheduledQuantity'] : null;
            $orderedQty = isset($item['orderedQuantity']) ? (float)$item['orderedQuantity'] : null;

            $quantity = $scheduledQty;
            if ($quantity === null || $quantity <= 0) {
                $quantity = $orderedQty;
            }
            if ($quantity === null) {
                $quantity = 0;
            }

            if ($price && $quantity > 0) {
                $totalAmount += $price * $quantity;
            }

            $masked = [
                'INDEX' => $item['index'] ?? '',
                'PRODUCTCODE' => $item['productCode'] ?? '',
                'PRODUCTNAME' => $item['productName'] ?? '',
                'BARCODE' => $barcode,
                'UNIT' => $item['unit'] ?? '',
                'PRICE' => $price,
                'QUANTITY' => $quantity,
                'ORDERED_QUANTITY' => $orderedQty !== null ? $orderedQty : 0,
                'SCHEDULED_QUANTITY' => $scheduledQty !== null ? $scheduledQty : 0,
                'NOTE1' => $item['note1'] ?? '',
                'NOTE2' => $item['note2'] ?? '',
            ];

            if ($barcode !== '') {
                $searchResult = $this->external_model->get_sku_from_barcode_v2($barcode, 'fast_barco');
                if ($searchResult) {
                    $masked['BARCODE'] = $searchResult['sku'];
                }
            }

            if ($masked['BARCODE'] !== '' && $masked['QUANTITY'] > 0) {
                $masked_items[] = $masked;
            }
        }

        $pageTotal = $totalAmount > 0 ? number_format($totalAmount, 2, '.', '') : '';
        $grandTotal = $pageTotal;

        $winmartCustomerMappings = [
            'ST000175' => [
                'name' => 'Chi Nhánh Bình Dương-Công Ty Cổ Phần Dịch Vụ Thương Mại Tổng Hợp Wincommerce',
                'keywords' => [
                    ['BINH', 'DUONG'],
                    ['THU', 'DAU', 'MOT'],
                    ['VSIP'],
                ],
            ],
            'ST000448' => [
                'name' => 'CHI NHÁNH HƯNG YÊN - CÔNG TY CỔ PHẦN DỊCH VỤ THƯƠNG MẠI TỔNG HỢP WINCOMMERCE',
                'keywords' => [
                    ['HUNG', 'YEN'],
                    ['YEN', 'MY'],
                ],
            ],
            'ST000106' => [
                'name' => 'Chi Nhánh Đà Nẵng–Công Ty Cổ Phần Dịch Vụ Thương Mại Tổng Hợp Wincommerce',
                'keywords' => [
                    ['DA', 'NANG'],
                    ['HOA', 'KHANH'],
                ],
            ],
            'ST000259' => [
                'name' => 'CHI NHÁNH BẮC NINH - CÔNG TY CỔ PHẦN DỊCH VỤ THƯƠNG MẠI TỔNG HỢP WINCOMMERCE',
                'keywords' => [
                    ['BAC', 'NINH'],
                ],
            ],
            'ST000095' => [
                'name' => 'Chi nhánh Hà Nội- Công Ty Cổ Phần Dịch Vụ Thương Mại Tổng Hợp Wincommerce',
                'keywords' => [
                    ['HA', 'NOI'],
                    ['HANOI'],
                ],
            ],
        ];

        $matchedCustomerCode = '';
        $matchedCustomerName = '';
        if (!empty($addressTokenSet)) {
            foreach ($winmartCustomerMappings as $customerCode => $meta) {
                $hasMatch = false;
                foreach ($meta['keywords'] as $keywordSet) {
                    $allTokensPresent = true;
                    foreach ($keywordSet as $keyword) {
                        $normalizedKeyword = $normalizeAddress($keyword);
                        if ($normalizedKeyword === '') {
                            continue;
                        }
                        $keywordTokens = array_filter(explode(' ', $normalizedKeyword));
                        foreach ($keywordTokens as $token) {
                            if ($token === '') {
                                continue;
                            }
                            if (!isset($addressTokenSet[$token])) {
                                $allTokensPresent = false;
                                break;
                            }
                        }
                        if (!$allTokensPresent) {
                            break;
                        }
                    }

                    if ($allTokensPresent) {
                        $hasMatch = true;
                        break;
                    }
                }

                if ($hasMatch) {
                    $matchedCustomerCode = $customerCode;
                    $matchedCustomerName = $meta['name'];
                    break;
                }
            }
        }

        $processed_order = [];
        if ($orderId !== '') {
            $uniquekey = 'WINMART_PO_' . $orderId;
            $check_order = $this->external_model->get_record($uniquekey);

            if (!$check_order) {
                $newId = $this->external_model->add_record([
                    'uniquekey' => $uniquekey,
                    'rel' => 'Order',
                    'root_id' => $orderId,
                    'target_id' => false,
                ]);

                if ($newId) {
                    $processed_order = [
                        'order' => [
                            'id' => $newId,
                            'uniquekey' => $uniquekey,
                            'rel' => 'Order',
                            'root_id' => $orderId,
                            'target_id' => false,
                            'DeliveryDate' => $deliveryDate,
                            'OrderDate' => $orderDate,
                            'OrderUrl' => $orderUrl,
                            'OrderGroup' => $orderGroup,
                            'ShipToName' => $shipToName,
                            'Supplier' => $supplierInfo['supplier'] ?? '',
                            'CustomerCode' => $matchedCustomerCode,
                            'CustomerName' => $matchedCustomerName,
                            'BuyerCode' => '',
                            'PageTotal' => $pageTotal,
                            'GrandTotal' => $grandTotal,
                        ],
                        'items' => $masked_items,
                    ];
                }
            }
        }

        echo json_encode([
            'processed_order' => $processed_order,
            'output' => $products,
            'headers' => $headers,
            'PONumber' => $orderId,
            'DeliveryDate' => $deliveryDate,
            'ShipToName' => $shipToName,
            'PageTotal' => $pageTotal,
            'GrandTotal' => $grandTotal,
            'CustomerCode' => $matchedCustomerCode,
            'CustomerName' => $matchedCustomerName,
            'BuyerCode' => '',
            'post' => $raw,
        ]);
    }

     // Đơn hàng Big C
     public function orders_crawl_bigc(){
        if (file_exists(APPPATH . 'libraries/App_simple_html_dom.php')) {
            // array_push($autoload['helper'], 'my_functions');
            include(APPPATH . 'libraries/App_simple_html_dom.php');
        }

        $this->load->model('external_model');
  
        $xml = $this->input->post('data');
        $xml = preg_replace('/<td[^>]*><\/td>/', '', $xml);

        $dom = new DOMDocument;
        @$dom->loadHTML(mb_convert_encoding($xml, 'HTML-ENTITIES', 'UTF-8'));

        // Create a new XPath instance
        $xpath = new DOMXPath($dom);
        // Get all tr elements in the table
        $rows = $xpath->query('//tr');

        // Đầu tiên cần tìm vị trí chưa hàng Page, bên dưới sẽ có danh sách sản phẩm
        $position = 0;
        // Iterate over each row
        foreach ($rows as $i => $row) {
            // Get all td elements in this row
            $cols = $xpath->query('td', $row);

            // Iterate over each column
            foreach ($cols as $col) {
                // If the column contains the text "Page", print the position (index) and break the loop
                if (strpos($col->nodeValue, 'Article') !== false) {
                    $position  = ($i) ;
                    break 2;
                }
            }
        }

         // Vị trí cuối
         $lastPosition = 0;
         // Iterate over each row
         foreach ($rows as $i => $row) {
             // Get all td elements in this row
             $cols = $xpath->query('td', $row);
 
             // Iterate over each column
             foreach ($cols as $col) {
                 // If the column contains the text "Page", print the position (index + 1) and break the loop
                 if (strpos($col->nodeValue, 'TOTAL BF.TAX') !== false) {
                    $lastPosition  = ($i-1) ;
                    break 2;
                 }
             }
        }

        // PO
        // $PONumber = "";
        // // Iterate over each row
        // foreach ($rows as $i => $row) {
        //     // Get all td elements in this row
        //     $cols = $xpath->query('td', $row);
        
        //     // Iterate over each column
        //     foreach ($cols as $j => $col) {
        //         // If the column contains the text "PO No.", get the order number and break the loop
        //         if (strpos($col->nodeValue, 'PO No.') !== false) {
        //             // Order number is in the next column (index $j + 2)
        //             if ($cols->length > $j + 2) {
        //                 $PONumber = trim($cols->item($j + 2)->nodeValue);
        //             }
        //             break 2;
        //         }
        //     }
        // }

        $PONumber = "";
        // Iterate over each row
        for ($i = 0; $i < $rows->length; $i++) {
            $row = $rows->item($i);
            // Get all td elements in this row
            $cols = $xpath->query('td', $row);

            // Iterate over each column
            foreach ($cols as $j => $col) {
                // If the column contains the text "Order No", get the order number from the first td of next tr
                if ($col->nodeValue === 'Order No' && $i < $rows->length - 1) {
                    $nextRow = $rows->item($i + 1);
                    $nextRowCols = $xpath->query('td', $nextRow);
                    $orderNo = trim($nextRowCols->item(0)->nodeValue);
                    $PONumber = $orderNo;
                    break 2;
                }
            }
        }

         // Delivery_to
         $Delivery_to = "";
         // Iterate over each row
         foreach ($rows as $i => $row) {
             // Get all td elements in this row
             $cols = $xpath->query('td', $row);
         
             // Iterate over each column
             foreach ($cols as $j => $col) {
                 // If the column contains the text "PO No.", get the order number and break the loop
                 if (strpos($col->nodeValue, 'Delivery to') !== false) {
                     // Order number is in the next column (index $j + 2)
                     if ($cols->length > $j + 2) {
                         $Delivery_to = trim($cols->item($j + 2)->nodeValue);
                     }
                     break 2;
                 }
             }
         }
        
        // headers của Emarts
        $headers = [];
        // if ($rows->length > $position ) {
        //     $row = $rows->item($position );
            
        //     // Get all td elements in this row
        //     $cols = $xpath->query('td', $row);
        //     // Extract the data from the td elements and add it to an array
           
        //     foreach ($cols as $col) {
        //         $value = $col->nodeValue;
        //         $value = mb_convert_encoding($value, 'UTF-8');
        //         $headers[] = $value;
        //     }
        // }
        // Hard Code Header
        $headers = ['Article', 'Article Desc', 'OUType', 'LV', 'SKU/OU', 'OU Qty', 'Free Qty', 'NetPurchase Price', 'Unit', 'Total NetPurchase Price'];

        // Đọc qua danh sách sản phẩm
        $products = [];
       // If both the start and end positions were found
        if ($position !== 0 && $lastPosition !== 0) {
            // // Process the product rows between the start and end positions
            for ($i = $position+1; $i < $lastPosition; $i++) {
                $row = $rows->item($i);
                // Get all td elements in this row
                $cols = $xpath->query('td', $row);
                $data = [];
                foreach ($cols as $j => $col) {
                    // Use the corresponding header as the key
                    if (isset($headers[$j])) {
                        $data[$headers[$j]] = $col->nodeValue;
                    }
                    // $products[] = $data;
                    // array_push($data, $products );
                }
                $products[] = $data;
            }
            // Process the product rows between the start and end positions
            // for ($i = $position+1; $i < $lastPosition; $i++) {
            //     $row = $rows->item($i);
            //     // Get all td elements in this row
            //     $cols = $xpath->query('td', $row);
            //     $data = [];
            //     $unitPrice = null;
            //     $totalPrice = null;
            //     foreach ($cols as $j => $col) {
            //         // Check if the previous and next-to-previous node values are numeric (which would likely represent prices)
            //         if ($j > 1 && is_numeric($cols->item($j-1)->nodeValue) && is_numeric($cols->item($j-2)->nodeValue) && !is_numeric($cols->item($j)->nodeValue)) {
            //             // Determine which is the unit price and which is the total price based on their values
            //             if ($cols->item($j-1)->nodeValue > $cols->item($j-2)->nodeValue) {
            //                 $unitPrice = $cols->item($j-1)->nodeValue;
            //                 $totalPrice = $cols->item($j-2)->nodeValue;
            //             } else {
            //                 $unitPrice = $cols->item($j-2)->nodeValue;
            //                 $totalPrice = $cols->item($j-1)->nodeValue;
            //             }
            //         }
            //         if (isset($headers[$j])) {
            //             $data[$headers[$j]] = $col->nodeValue;
            //         }
            //     }
            //     // Add the prices to the data
            //     if ($unitPrice !== null && $totalPrice !== null) {
            //         $data["NetPurchase Price"] = $unitPrice;
            //         $data["Total Price"] = $totalPrice;
            //     }
            //     $products[] = $data;
            // }
        }
        


        $uniquekey = "BIGC_PO_".$PONumber;
        $check_order = $this->external_model->get_record($uniquekey);
        $processed_order = [];
        if ( !$check_order ){
            // Đơn hàng chưa tạo
            $qresult = $this->external_model->add_record(array(
                "uniquekey" => $uniquekey,
                "rel" => 'Order',
                "root_id" => $PONumber,
                "target_id" => false
            ));
    
            if ( $qresult ){
                $masked_items = array();
                // Xử lý sản phẩm:
                foreach ($products as $item){
                    if (!array_key_exists('Article', $item) || !array_key_exists('SKU/OU', $item) || !array_key_exists('NetPurchase Price', $item)  || !array_key_exists('NetPurchase Price', $item) ) continue;

                    $m_order_item = $item;
                    $sku = $item['Article'];
                    // var_dump
                    ob_start();
                    var_dump($item);
                    $debug_dump = ob_get_clean();
                    $result = $this->external_model->get_sku_from_barcode_v2($sku,"fast_barco");
                    
                    if ($result){  
                        $m_order_item['BARCODE'] = $result['sku'];
                    } /*else {
                        $seach_emart = $this->external_model->get_sku_from_barcode_v2($sku,"fast_barco");
                        if ($seach_emart){  
                            $m_order_item['BARCODE'] = $seach_emart['sku'];
                        }
                    } */
                    $m_order_item['PRODUCTNAME'] = $item['Article Desc'];
                    // $string = $item['NetPurchase Price'];
                    $m_order_item['PRICE'] = str_replace('.', '',$item['NetPurchase Price']);
                    // $isNumber = is_numeric( $string);
                    // if ( $isNumber ){
                    //     $m_order_item['PRICE'] = str_replace('.', '',$item['NetPurchase Price']);
                    // } else {
                    //     $m_order_item['PRICE'] = 0;
                    // }
                //    
                    $m_order_item['QUANTITY'] = $item['SKU/OU'] * $item['OU Qty'];

                    if ($item['Article'] != "" && $item['SKU/OU'] != ""){
                        $masked_items[] = $m_order_item;
                    }
                  
                  
                }

                $processed_order = array(
                    'order' => array(
                        "id" => $qresult,
                        "uniquekey" => $uniquekey,
                        "rel" => 'Order',
                        "root_id" => $PONumber,
                        "target_id" => false,
                        'Delivery_to' => $Delivery_to
                    ),
                    'items' => $masked_items,
                    // 'old_items' => $order
                );
            }
        }

        echo json_encode( array(
            'processed_order' =>  $processed_order, 
            'output' =>  $products, 
            'headers' =>  $headers, 
            'firstPosition' =>  $position, 
            'lastPosition' =>  $lastPosition, 
            'PONumber' =>  $PONumber, 
            "post" => $xml,
           
        ));
        

        // $html = str_get_html($data);
        // Find the first table in the HTML

        
        // $table = $html->find('table');
        // echo json_encode( array(
        //     'data' => $table
        // ));
        return;
        
        die();
    }

    public function orders_crawl_bigc_v2() {
        if (file_exists(APPPATH . 'libraries/App_simple_html_dom.php')) {
            include(APPPATH . 'libraries/App_simple_html_dom.php');
        }
        $this->load->model('external_model');
        
        $xml = $this->input->post('data');
        // Xoá các <td></td> rỗng
        $xml = preg_replace('/<td[^>]*><\/td>/', '', $xml);
    
        $dom = new DOMDocument;
        // Load HTML dạng 'HTML-ENTITIES' để tránh lỗi encoding
        @$dom->loadHTML(mb_convert_encoding($xml, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);
        $rows = $xpath->query('//tr');
    
        // 1) Tìm dòng header (chứa "Article")
        $position = 0;
        $headerLabels = [];
        foreach ($rows as $i => $row) {
            $cols = $xpath->query('td', $row);
            foreach ($cols as $col) {
                if (strpos($col->nodeValue, 'Article') !== false) {
                    $position = $i;
                    for ($h = 0; $h < $cols->length; $h++) {
                        $label = $cols->item($h)->nodeValue;
                        // Xoá xuống dòng, khoảng trắng dư
                        $label = preg_replace('/[\r\n]+/', ' ', $label);
                        $label = preg_replace('/\s+/', ' ', $label);
                        $label = trim($label);
                        // Có thể xoá hoàn toàn mọi dấu cách còn lại trong tên cột
                        $label = str_replace(' ', '', $label);
                        $headerLabels[$h] = $label;
                    }
                    break 2;
                }
            }
        }
    
        // 2) Tìm lastPosition (tỉ lệ cột rỗng > 50%)
        $lastPosition = 0;
        for ($i = $position + 1; $i < $rows->length; $i++) {
            $row = $rows->item($i);
            $cols = $xpath->query('td', $row);
            if ($cols->length > 0) {
                $totalCols = $cols->length;
                $emptyCount = 0;
                for ($c = 0; $c < $totalCols; $c++) {
                    $val = trim($cols->item($c)->nodeValue);
                    if ($val === '' || mb_strtolower($val) === 'nan') {
                        $emptyCount++;
                    }
                }
                if ($emptyCount / $totalCols > 0.5) {
                    $lastPosition = $i - 1;
                    break;
                }
            }
        }
        if ($lastPosition === 0) {
            $lastPosition = $rows->length - 1;
        }
    
        // 3) Lấy PONumber
        $PONumber = "";
        for ($i = 0; $i < $rows->length; $i++) {
            $row = $rows->item($i);
            $cols = $xpath->query('td', $row);
            foreach ($cols as $col) {
                if ($col->nodeValue === 'Order No' && $i < $rows->length - 1) {
                    $nextRow = $rows->item($i + 1);
                    $nextRowCols = $xpath->query('td', $nextRow);
                    $PONumber = trim($nextRowCols->item(0)->nodeValue);
                    break 2;
                }
            }
        }
    
        // 4) Lấy Delivery_to
        $Delivery_to = "";
        foreach ($rows as $i => $row) {
            $cols = $xpath->query('td', $row);
            foreach ($cols as $j => $col) {
                if (strpos($col->nodeValue, 'Delivery to') !== false) {
                    if ($cols->length > $j + 2) {
                        $Delivery_to = trim($cols->item($j + 2)->nodeValue);
                    }
                    break 2;
                }
            }
        }
    
        // 5) Parse sản phẩm
        $products = [];
        if ($position !== 0 && $lastPosition > $position) {
            for ($i = $position + 1; $i <= $lastPosition; $i++) {
                $row = $rows->item($i);
                $cols = $xpath->query('td', $row);
                $data = [];
                for ($j = 0; $j < $cols->length; $j++) {
                    if (isset($headerLabels[$j])) {
                        $colName = $headerLabels[$j];
                        $val = $cols->item($j)->nodeValue;
                        $val = preg_replace('/[\r\n]+/', ' ', $val);
                        $val = preg_replace('/\s+/', ' ', $val);
                        $val = trim($val);
                        $data[$colName] = $val;
                    }
                }
                $products[] = $data;
            }
        }
    
        // 6) Lưu DB
        $uniquekey = "BIGC_PO_" . $PONumber;
        $check_order = $this->external_model->get_record($uniquekey);
        $processed_order = [];
    
        if (!$check_order) {
            $qresult = $this->external_model->add_record([
                "uniquekey" => $uniquekey,
                "rel"       => 'Order',
                "root_id"   => $PONumber,
                "target_id" => false
            ]);
    
            if ($qresult) {
                $masked_items = [];
                $error_masked_items = [];
                foreach ($products as $item) {
                    if (!isset($item['Article']) && !isset($item['article'])) {
                        continue;
                    }
                
                    // Kiểm tra các trường hợp có thể cho cột Net Purchase Price
                    $netPriceCol = isset($item['NetPurchasePrice']) ? $item['NetPurchasePrice'] : (
                        isset($item['Net\rPurchasePrice']) ? $item['Net\rPurchasePrice'] : (
                        isset($item['netpurchaseprice']) ? $item['netpurchaseprice'] : null
                    ));
                
                    // Kiểm tra các trường hợp có thể cho cột SKU/OU
                    $skuOuCol = isset($item['SKU/OU']) ? $item['SKU/OU'] : (
                        isset($item['SKUOU']) ? $item['SKUOU'] : (
                        isset($item['sku/ou']) ? $item['sku/ou'] : null
                    ));
                
                    if (!$netPriceCol || !$skuOuCol) {
                        $error_masked_items[] = $item;
                        continue;
                    }
                
                    $sku = isset($item['Article']) ? $item['Article'] : $item['article'];
                    $result = $this->external_model->get_sku_from_barcode_v2($sku, "fast_barco");
                    if ($result) {
                        $item['BARCODE'] = $result['sku'];
                    }
                
                    // Kiểm tra các trường hợp có thể cho cột Article Description
                    $item['PRODUCTNAME'] = isset($item['ArticleDesc']) ? $item['ArticleDesc'] : (
                        isset($item['articledesc']) ? $item['articledesc'] : ''
                    );
                
                    $item['PRICE'] = str_replace('.', '', $netPriceCol);
                
                    $qtySKU = floatval($skuOuCol);
                    $qtyOU = isset($item['OUQty']) ? floatval($item['OUQty']) : (
                        isset($item['ouqty']) ? floatval($item['ouqty']) : 0
                    );
                    
                    $item['QUANTITY'] = $qtySKU * $qtyOU;
                
                    if ($sku !== '' && $item['QUANTITY'] > 0) {
                        $masked_items[] = $item;
                    } else {
                        $error_masked_items[] = $item;
                    }
                }
                
    
                $processed_order = [
                    'order' => [
                        "id"          => $qresult,
                        "uniquekey"   => $uniquekey,
                        "rel"         => 'Order',
                        "root_id"     => $PONumber,
                        "target_id"   => false,
                        'Delivery_to' => $Delivery_to
                    ],
                    'items'             => $masked_items,
                    "error_masked_items"=> $error_masked_items
                ];
            }
        }
    
        echo json_encode([
            'processed_order' => $processed_order,
            'output'          => $products,
            'headers'         => $headerLabels,
            'firstPosition'   => $position,
            'lastPosition'    => $lastPosition,
            'PONumber'        => $PONumber,
            'post'            => $xml
        ]);
        return;
    }
 

    /**
     * Kiểm tra xem một row có phải rỗng hoặc 'NaN' toàn bộ không.
     * - Duyệt tất cả cột
     * - Nếu thấy cột nào != '' và != 'NaN' => row không rỗng
     */
    private function isEmptyRow($cols)
    {
        if (!$cols || $cols->length === 0) {
            return true;
        }
        foreach ($cols as $col) {
            $val = mb_strtolower(trim($col->nodeValue));
            if ($val !== '' && $val !== 'nan') {
                return false;
            }
        }
        return true;
    }


    // public function orders_crawl_coop(){
    //     $data = $this->input->post('data');
    //     $this->load->model('external_model');
    //     $order = json_decode($data, true);
      
    //     if ($order){
          
    //         $orderNumber = trim($order['orderNumber']);
    //         $uniquekey = trim($order['uniquekey']);
    //         $skus_string = $order['skus'];

    //         $skus = json_decode( $skus_string, true);

    //         // Check if the order already exists
    //         $check_order = $this->external_model->get_record($uniquekey);
    //         if (!$check_order && $skus ){
    //             // Create a new order record if it doesn't exist
    //             $result = $this->external_model->add_record(array(
    //                 "uniquekey" => $uniquekey,
    //                 "rel" => 'Order',
    //                 "root_id" => $orderNumber,
    //                 "target_id" => false
    //             ));
                
    //             if ($result){
    //                 $processed_skus = [];
    //                 foreach ($skus as $sku_item){
    //                     // Process each SKU item here
    //                     // For example, you might want to update inventory or prices
    //                     // This is where you'd integrate with your external_model or other business logic
                       

    //                     $sku_id = $sku_item["SKU"];
    //                     $string  = $sku_id ;
    //                     $lastDashPos = strrpos($sku_id, '-');
    
    //                     // If the string contains a dash, remove everything after the last dash
    //                     if ($lastDashPos !== false) {
    //                         $sku_id = substr($string, 0, $lastDashPos);
    //                     }
                        
                       
    //                     // var_dump
    //                     // ob_start();
    //                     // var_dump($sku_item);
    //                     // $debug_dump = ob_get_clean();

    //                     // log_message('error', 'debug_dump: '. $debug_dump );

    //                     $searched_sku = $this->external_model->get_sku_from_barcode_v2($sku_id,"fast_barco");
    //                     if ( $searched_sku ){
    //                         $searched_sku = $searched_sku["sku"];
    //                     }

    //                     // Add the processed SKU to the processed_skus array
    //                     $processed_skus[] = [
    //                         "SKU" => $sku_item["SKU"],
    //                         "FAST_SKU" => $searched_sku,
    //                         "Quantity" => $sku_item["Quantity"],
    //                         "Cost" => $sku_item["Cost"],
    //                         "Description" => $sku_item["Description"],
    //                     ];
    //                 }

    //                 // Add the processed order to the processed_orders array
    //                 $processed_order = array(
    //                     'order' => array(
    //                         "id" => $result,
    //                         "uniquekey" => $uniquekey,
    //                         "rel" => 'Order',
    //                         "root_id" => $orderNumber,
    //                         "target_id" => false
    //                     ),
    //                     'items' => $processed_skus
    //                 );
    //             }
    //         }
    //         echo json_encode( $processed_order  );
    //     }
    //     die();
    // }

    public function orders_crawl_coop(){
        $data = $this->input->post('data');
        $this->load->model('external_model');
        $order = json_decode($data, true);
      
        if ($order){
            $orderNumber = trim($order['orderNumber']);
            $uniquekey = trim($order['uniquekey']);
            $skus_string = $order['skus'];
    
            $skus = json_decode($skus_string, true);
    
            // Kiểm tra xem đơn hàng đã tồn tại hay chưa
            $check_order = $this->external_model->get_record($uniquekey);
            
            if ($skus ){
                $processed_skus = [];
                foreach ($skus as $sku_item){
                    // Xử lý từng mục SKU giống như trong mã gốc
                    $sku_id = $sku_item["SKU"];
                    $string  = $sku_id;
                    $lastDashPos = strrpos($sku_id, '-');
    
                    // Nếu chuỗi chứa dấu gạch ngang, loại bỏ mọi thứ sau dấu gạch ngang cuối cùng
                    if ($lastDashPos !== false) {
                        $sku_id = substr($string, 0, $lastDashPos);
                    }
                    
                    // Tìm SKU đã xử lý
                    $searched_sku = $this->external_model->get_sku_from_barcode_v2($sku_id, "fast_barco");
                    if ($searched_sku ){
                        $searched_sku = $searched_sku["sku"];
                    }
    
                    // Thêm SKU đã xử lý vào mảng
                    $processed_skus[] = [
                        "SKU" => $sku_item["SKU"],
                        "FAST_SKU" => $searched_sku,
                        "Quantity" => $sku_item["Quantity"],
                        "Cost" => $sku_item["Cost"],
                        "Description" => $sku_item["Description"],
                    ];
                }
    
                if (!$check_order){
                    // Nếu đơn hàng chưa tồn tại, tạo mới đơn hàng
                    $result = $this->external_model->add_record(array(
                        "uniquekey" => $uniquekey,
                        "rel" => 'Order',
                        "root_id" => $orderNumber,
                        "target_id" => false
                    ));
                    
                    if ($result){
                        $processed_order = array(
                            'order' => array(
                                "id" => $result,
                                "uniquekey" => $uniquekey,
                                "rel" => 'Order',
                                "root_id" => $orderNumber,
                                "target_id" => false
                            ),
                            'items' => $processed_skus
                        );
                    }
                }
                else {
                    // Nếu đơn hàng đã tồn tại, lấy thông tin đơn hàng hiện có
                    $processed_order = array(
                        'order' => array(
                            "id" => $check_order->id, // Điều chỉnh theo cấu trúc thực tế của $check_order
                            "uniquekey" => $check_order->uniquekey,
                            "rel" => $check_order->rel,
                            "root_id" => $check_order->root_id,
                            "target_id" => $check_order->target_id
                        ),
                        'items' => $processed_skus
                    );
                }
            }
    
            // Trả về kết quả dưới dạng JSON
            echo json_encode($processed_order);
        }
        die();
    }
    
    
    

    // API Van Don 
    public function step_1_api_thong_tin_vandon_CJ(){
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');

        // header('Transfer-Encoding: chunked');
        // header('Transfer-Encoding: chunked');
        // header('Content-Encoding', 'chunked');
        // header('Transfer-Encoding', 'chunked');
        $dvvc = [
            1 => "CJ Logistics",
            2 => "DHL Express",
            3 => "Australia Post",
            4 => "Yamato Japan",
            5 => "CJ Logistics Global",
            6 => "CJ Packet",
            7 => "GDEX",
            8 => "DPD",
            9 => "T Cat",
            10 => "HCT Express",
            11 => "Sagawa",
            12 => "fedex",
            12 => "ups",
            13 => "sagawa-global",
        ];
        $status_vals = [
            "pending" => "Gói mới được thêm vào đang chờ theo dõi",
            "notfound" => "Thông tin theo dõi gói hàng vẫn chưa có sẵn",
            "transit" => "Chuyển phát nhanh đã nhận gói hàng từ người giao hàng, gói hàng đang trên đường đến điểm đến",
            "pickup" => "Còn được gọi là 'Out For Delivery', người chuyển phát nhanh sắp giao gói hàng hoặc gói hàng đang chờ người nhận đến nhận",
            "delivered" => "Gói hàng đã được giao thành công",
            "expired" => "Không có thông tin theo dõi trong 30 ngày đối với dịch vụ chuyển phát nhanh hoặc không có thông tin theo dõi trong 60 ngày đối với dịch vụ bưu điện kể từ khi gói được thêm vào",
            "undelivered" => "Còn được gọi là 'Không thành công', người chuyển phát nhanh đã cố gắng giao hàng nhưng không thành công, thường để lại thông báo và sẽ cố gắng giao hàng lại",
            "exception" => "Gói hàng bị bỏ lỡ, người nhận địa chỉ trả lại gói hàng cho người gửi hoặc các trường hợp ngoại lệ khác",
            "InfoReceived" => "Người vận chuyển đã nhận được yêu cầu từ người gửi hàng và sắp nhận hàng"
        
        ];
        $json = file_get_contents('php://input');
        if ($this->input->post() || $json){
            $data = json_decode($json, true );
            
            $response = '{
                "version": "chatbot",
                "content": {
                    "messages": [
                        {
                            "type": "text",
                            "text": "#ketquatracuu"
                        },
                        {
                            "type": "text",
                            "text": "#ketquavandon"
                        }
                    ],
                    "actions": []
                }
            }';
           
            if ( $data ){
                $donvivanchuyen = $data['donvivanchuyen'];
                $mavandon = $data['mavandon'];
            } else {
                $donvivanchuyen = $this->input->post('donvivanchuyen');
                $mavandon = $this->input->post('mavandon');
            }
            $can_kiem_tra_chuoi_tra_cuu = false;
            $testing_chuoi = $mavandon;
            if ( $this->kiemTraChuoi($mavandon) == false ){
                $mavandon = str_replace(",", "", $mavandon);
                $testing_chuoi = $mavandon;
            } else {
                $testing_chuoi = explode(",", $mavandon)[0];
                $can_kiem_tra_chuoi_tra_cuu = true;
            }
            // 514629917885 // 
            $regexes = [
                // '/^514/',
                '/^5\d{11}$/',  // CJ Logistic
                // '/^56\d{10}$/',  // Sagawa Logistic 
                '/^9\d{11}$/',  // t-cat
                '/^6\d{9}$/',   // HTC
                '/^4\d{10}$/',    // Cagopex
                '/^7\d{11}$/',  // Fedex Logistic
                '/^1Z\d{16}$/',  // UPS
               
            ];
            
            $matched = false;

            foreach ($regexes as $key => $regex) {
                if (preg_match($regex, $testing_chuoi)) {
                    // echo "Mã vận đơn '{$mavandon}' thuộc về đơn vị vận chuyển: ";
                    switch ($key) {
                        case 0:
                             $donvivanchuyen = 1;
                         
                            break;
                        case 8:
                           
                                break;
                        case 2:
                            // echo "t-cat";
                            $donvivanchuyen = 9;
                            break;
                        case 3:
                            // echo "HCT";
                            $donvivanchuyen = 10;
                            break;
                        case 4:
                            // echo "Cagopex";
                            // $donvivanchuyen = 'HCT Express';
                            break;
                        case 5:
                                // echo "Cagopex";
                            $donvivanchuyen = 12;
                            break;
                        case 6:
                                // echo "Cagopex";
                            $donvivanchuyen = 12;
                            break;
                        case 7:
                                // echo "Cagopex";
                            $donvivanchuyen = 13;
                            break;
                       
                        case 1:
                                // echo "Cagopex";
                            $donvivanchuyen = 14;
                            break;
                       
                    }
                      // echo "\n";
                    $matched = true;
                    break;
                }
              
                
            }
            if (!$matched) {
                // echo "Mã vận đơn '{$code}' không thuộc về bất kỳ đơn vị vận chuyển nào.";
                // echo "\n";
            }

            // log_message('error', 'testing_chuoi: '. $testing_chuoi  . '|donvivanchuyen: ' . $donvivanchuyen);
            $nhatkyvanchuyen = $this->api_thong_tin_vandon($dvvc[$donvivanchuyen], $mavandon);
           
            if ($nhatkyvanchuyen && $nhatkyvanchuyen['data']){
               
                $delivery_datas = $nhatkyvanchuyen['data'];
                // $response = str_replace("#ketquatracuu","ĐƠN HÀNG ĐÃ TẠO VẬN ĐƠN", $response);
                $messages = [];
                if ( $delivery_datas && count($delivery_datas) > 0){
                   //  log_message('error', 'step_1_api_thong_tin_vandon_CJ: '. json_encode($delivery_datas));

                    // $first_status = $delivery_datas[0] ? $delivery_datas[0]['delivery_status'] : false;
                    // if (!$first_status || $first_status == 'notfound'){
                    //     $message['type'] = 'text';
                    //     $message['text'] = 'VẬN ĐƠN NÀY KHÔNG CÓ THÔNG TIN. CÓ THỂ HÀNG CHƯA ĐƯỢC CHUYỂN ĐI HOẶC SAI ĐƠN VỊ VẬN CHUYỂN | LOG:'.$dvvc[$donvivanchuyen] . '|' .  $mavandon;
                    //     $messages[] = $message;
                    //     $json_response_decode = json_decode($response);
                    //     $json_response_decode->content->messages = $messages;
                    //     // $response_string = json_encode($json_response_decode, true);
                    //     $CI = &get_instance();
                    //     $CI->output
                    //     ->set_content_type('application/json')
                    //     ->set_output(json_encode($json_response_decode));
                    //     return;
                    // }
                    $CI = &get_instance();
                    $CI->output
                    // ->set_header('Content-Length: ' . strlen(json_encode($json_response_decode)))
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array(
                        'html' => $this->step_2_tao_html_van_don($donvivanchuyen, $delivery_datas)
                    )));
                    // return $CI->response->setJSON($json_response_decode);
                    // echo $response_string;
                    return;
                    // foreach($delivery_datas as $delivery_data){
                    //     $delivery_status = $status_vals[$delivery_data['delivery_status']];
                    //     $status_number = $delivery_data['delivery_status'];
                    
                    //     $message['type'] = 'text';
                    //     $message['text'] =  'TRẠNG THÁI VẬN ĐƠN: ' . $delivery_status;

                    //     $messages[] = $message;
                    //     $trackinfos = $delivery_data['origin_info']['trackinfo']; 
                    //     $index = 1;
                    //     if (count($trackinfos) > 0){
                    //         // echo step_2_tao_html_van_don($trackinfos, $delivery_datas);
                    //         $CI = &get_instance();
                    //         $CI->output
                    //         // ->set_header('Content-Length: ' . strlen(json_encode($json_response_decode)))
                    //         ->set_content_type('application/json')
                    //         ->set_output(json_encode(array(
                    //             'html' => $this->step_2_tao_html_van_don($delivery_datas)
                    //         )));
                    //         // return $CI->response->setJSON($json_response_decode);
                    //         // echo $response_string;
                    //         return;
                    //     }
                       
                    // }
                } else {
                    $message['type'] = 'text';
                    $message['text'] = 'VẬN ĐƠN NÀY KHÔNG CÓ THÔNG TIN. CÓ THỂ HÀNG CHƯA ĐƯỢC CHUYỂN ĐI HOẶC SAI ĐƠN VỊ VẬN CHUYỂN | LOG:'.$dvvc[$donvivanchuyen] . '|' .  $mavandon;
                    $messages[] = $message;
                } 
                
                // header('Content-Length: ' . 10);
                $json_response_decode = json_decode($response);
                $json_response_decode->content->messages = $messages;
                // $response_string = json_encode($json_response_decode, true);
                $CI = &get_instance();
                $CI->output
                // ->set_header('Content-Length: ' . strlen(json_encode($json_response_decode)))
                ->set_content_type('application/json')
                ->set_output(json_encode($json_response_decode));
                // return $CI->response->setJSON($json_response_decode);
                // echo $response_string;
                return;
            } else {
                
                $response_vandon = $this->taovandon($dvvc[$donvivanchuyen], $mavandon);
                // $response = str_replace("#ketquatracuu","KHÔNG TÌM THẤY THÔNG TIN ĐƠN HÀNG - VUI LÒNG LIÊN HỆ NHÂN VIÊN CSKH", $response);
                // $response = str_replace("#ketquavandon","KHÔNG TÌM THẤY VẬN ĐƠN - VUI LÒNG LIÊN HỆ NHÂN VIÊN CSKH [ LOG: " . $dvvc[$donvivanchuyen] . '|' .  $mavandon,  $response);

                // Khởi tạo đơn hàng trên hệ thống

                // log_message('error', 'response_vandon: '. json_encode($response_vandon));
                if ($mavandon == "" || $mavandon == 'undefined'){
                      // $response = str_replace("#ketquatracuu","KHÔNG TẠO ĐƯỢC TRA CỨU VẬN ĐƠN", $response);
                      $response = str_replace("#ketquatracuu","-", $response);
                      $response = str_replace("#ketquavandon","Lỗi " . $dvvc[$donvivanchuyen] . '|' .  $mavandon,  $response);
                      echo ($response );
                      die();
                }
                if ( $response_vandon->result['code'] == 200){
                    // $response = str_replace("#ketquatracuu","ĐÃ TẠO TRA CỨU VẬN ĐƠN THÀNH CÔNG, VUI LÒNG THỰC HIỆN TÌM KIẾM 1 LẦN NỮA ĐỂ XEM KẾT QUẢ", $response);
                    $response = str_replace("#ketquatracuu","reload", $response);
                    $response = str_replace("#ketquavandon","MÃ VẬN ĐƠN CỦA BẠN LÀ: " . $dvvc[$donvivanchuyen] . '|' .  $mavandon,  $response);
    
                } else {
                    // $response = str_replace("#ketquatracuu","KHÔNG TẠO ĐƯỢC TRA CỨU VẬN ĐƠN", $response);
                    $response = str_replace("#ketquatracuu","reload", $response);
                    $response = str_replace("#ketquavandon","MÃ VẬN ĐƠN CỦA BẠN LÀ: " . $dvvc[$donvivanchuyen] . '|' .  $mavandon,  $response);
                }

                echo ($response );
                die();
            }

            echo $response;
           

            return;
            
           
        }
    }

    function kiemTraChuoi($chuoi) {
        $phanCach = ",";
        return (strpos($chuoi, $phanCach) !== false) ? true : false;
    }
    

    /**
     * 
     */
    function api_thong_tin_vandon($donvivanchuyen, $mavandon){
        $status_vals = [
            "pending" => "Gói mới được thêm vào đang chờ theo dõi",
            "notfound" => "Thông tin theo dõi gói hàng vẫn chưa có sẵn",
            "transit" => "Chuyển phát nhanh đã nhận gói hàng từ người giao hàng, gói hàng đang trên đường đến điểm đến",
            "pickup" => "Còn được gọi là 'Out For Delivery', người chuyển phát nhanh sắp giao gói hàng hoặc gói hàng đang chờ người nhận đến nhận",
            "delivered" => "Gói hàng đã được giao thành công",
            "expired" => "Không có thông tin theo dõi trong 30 ngày đối với dịch vụ chuyển phát nhanh hoặc không có thông tin theo dõi trong 60 ngày đối với dịch vụ bưu điện kể từ khi gói được thêm vào",
            "undelivered" => "Còn được gọi là 'Không thành công', người chuyển phát nhanh đã cố gắng giao hàng nhưng không thành công, thường để lại thông báo và sẽ cố gắng giao hàng lại",
            "exception" => "Gói hàng bị bỏ lỡ, người nhận địa chỉ trả lại gói hàng cho người gửi hoặc các trường hợp ngoại lệ khác",
            "InfoReceived" => "Người vận chuyển đã nhận được yêu cầu từ người gửi hàng và sắp nhận hàng"
        
        ];
        $khongtimthayvandon = false;
        $danhsachvanchuyen = [];
        $madonvivanchuyen = false;
        $diachinhanhang = false;
        $company = false;
        $contact_email = false;
        $check_order_json = false;
      
        if ($donvivanchuyen != null && $mavandon != null){
            $curl = curl_init();
        
            curl_setopt_array($curl, array(
              CURLOPT_URL => 'https://api.trackingmore.com/v3/trackings/get?tracking_numbers='.$mavandon,
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'GET',
              CURLOPT_HTTPHEADER => array(
                'Tracking-Api-Key: k5jfjayo-afzr-6vcq-i0e6-ks7kxtrecc43'
              ),
            ));
            
            $response = curl_exec($curl);
            
            curl_close($curl);

            // echo '<code>'.$response.'</code>';
            if ($response){
                $check_order_json = json_decode($response, true);
        
                if ($check_order_json['code'] == 200){
                    // echo '<p>Chưa tạo tra cứu vận đơn</p>';
                    $khongtimthayvandon  = true;
                }
            }
            // return $check_order_json;
            //  Danh sách DVVC
            $curl = curl_init();
        
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.trackingmore.com/v3/trackings/courier',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Tracking-Api-Key: k5jfjayo-afzr-6vcq-i0e6-ks7kxtrecc43'
                ),
            ));
        
            $response = curl_exec($curl);
        
            curl_close($curl);
            
            if ( $response ){
                $dsvanchuyen_obj = json_decode($response, true);
                if ($dsvanchuyen_obj['code'] == 200){
                    $danhsachvanchuyen =  $dsvanchuyen_obj['data'];
                    if (count($danhsachvanchuyen) > 0){
                        foreach($danhsachvanchuyen as $dvvc){
                            // log_message('error', 'danhsachvanchuyen: '. $dvvc['courier_name'] . "| donvivanchuyen: " . $donvivanchuyen);
                            if ($dvvc['courier_name'] == $donvivanchuyen ){
                                $madonvivanchuyen  = $dvvc['courier_code'];
                            }
                             
                        }
                    }
                }
            }
        
            // $data = get_customer_info($estimate, 'estimate', 'shipping');
            // $diachinhanhang =  $data['data']->shipping_street. ' ' . $data['data']->shipping_city;
            // $company = $data['data']->client->company;
            // $userid = $data['data']->clientid;
        
            // $CI = &get_instance();
            // $contact = $CI->clients_model->get_contact(get_primary_contact_user_id($userid));
            // $contact_email   = $contact ? $contact->email : '';
        
            return $check_order_json;
        } else {
            return null;
        }
    }

    function taovandon($donvivanchuyen, $tracking_number){
        //  Danh sách DVVC
        $curl = curl_init();
        $order_number = $tracking_number;
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.trackingmore.com/v3/trackings/courier',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Tracking-Api-Key: k5jfjayo-afzr-6vcq-i0e6-ks7kxtrecc43'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        if ( $response ){
            $dsvanchuyen_obj = json_decode($response, true);
            if ($dsvanchuyen_obj['code'] == 200){
                $danhsachvanchuyen =  $dsvanchuyen_obj['data'];
                if (count($danhsachvanchuyen) > 0){
                    foreach($danhsachvanchuyen as $dvvc){
                        if ($dvvc['courier_name'] == $donvivanchuyen ){
                            $courier_code  = $dvvc['courier_code'];
                        }
                        
                    }
                }
            }
        }

        Header('Access-Control-Allow-Origin: *'); //for allow any domain, insecure
        Header('Access-Control-Allow-Headers: *'); //for allow any headers, insecure
        Header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE'); //method allowed

        $tracking_numbers = explode(",", $tracking_number); // split the string into an array using comma delimiter

        if (count($tracking_numbers) == 1) {
            // single tracking number
            
            $curl = curl_init();
            // $order_number = rand(10,15).$tracking_number;
            
            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.trackingmore.com/v3/trackings/create',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'[
                {
                    "tracking_number": "'.$tracking_number.'",
                    "courier_code":"'.$courier_code.'",
                    "order_number": "'. $order_number .'",
                    "note": "Đơn hàng tra cứu từ ZALO BOT",
                    "customer_name": "Interlink BOT",
                    "customer_email": "info@chantroituonglai.com",
                    "title":"Đơn hàng tra cứu từ ZALO BOT"
                }
            ]',
            CURLOPT_HTTPHEADER => array(
                'Tracking-Api-Key: k5jfjayo-afzr-6vcq-i0e6-ks7kxtrecc43',
                'Content-Type: application/json'
            ),
            ));
            
            $response = curl_exec($curl);
        } else {
            // multiple tracking numbers
        
            foreach ($tracking_numbers as $tracking_number) {
                $curl = curl_init();
                // $order_number = rand(10,15).$tracking_number;
                
                curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.trackingmore.com/v3/trackings/create',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>'[
                    {
                        "tracking_number": "'.$tracking_number.'",
                        "courier_code":"'.$courier_code.'",
                        "order_number": "'. $order_number .'",
                        "note": "Đơn hàng tra cứu từ FHC Global Tracking BOT",
                        "customer_name": "FHC Global Tracking BOT",
                        "customer_email": "info@chantroituonglai.com",
                        "title":"Đơn hàng tra cứu từ Website"
                    }
                ]',
                CURLOPT_HTTPHEADER => array(
                    'Tracking-Api-Key: k5jfjayo-afzr-6vcq-i0e6-ks7kxtrecc43',
                    'Content-Type: application/json'
                ),
                ));
                
                $response = curl_exec($curl);
            }
        }

      
        return array(
            'result' =>  json_decode($response, true) ,
            'data' => '[
                {
                    "tracking_number": "'.$tracking_number.'",
                    "courier_code":"'.$courier_code.'",
                    "order_number": "'. $order_number .'",
                    "note": "Đơn hàng tra cứu từ ZALO BOT",
                    "customer_name": "Interlink BOT",
                    "customer_email": "info@chantroituonglai.com",
                    "title":"Đơn hàng tra cứu từ ZALO BOT"
                }
            ]'
        ) ;
    }


    function step_2_tao_html_van_don($donvivanchuyen, $delivery_datas){
        $html = <<<EOT
            <style>
            .section-js-tracuuvandon-js img.icon {
                width: 45px !important;
            }

            .step-wrapper * {
                line-height: 1.2em;
                margin-bottom: 5px;
            }
            @keyframes jump {
                0%, 80%, 100% {
                transform: translateY(0);
                }
                40% {
                transform: translateY(-15px);
                }
            }
            
            .jumping-char {
                display: inline-block;
                animation: jump 1s linear infinite;
            }
            
            .jumping-char:nth-child(2) {
                animation-delay: 0.1s;
            }
            
            .jumping-char:nth-child(3) {
                animation-delay: 0.2s;
            }
            
            .jumping-char:nth-child(4) {
                animation-delay: 0.3s;
            }
            
            .jumping-char:nth-child(5) {
                animation-delay: 0.4s;
            }
            
            .jumping-char:nth-child(6) {
                animation-delay: 0.5s;
            }
            
            .jumping-char:nth-child(7) {
                animation-delay: 0.6s;
            }
            
            .jumping-char:nth-child(8) {
                animation-delay: 0.7s;
            }
            
            .jumping-char:nth-child(9) {
                animation-delay: 0.8s;
            }
            
            .jumping-char:nth-child(10) {
                animation-delay: 0.9s;
            }
            .loading-container {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
            }
            
            .loading-text {
                text-align: center;
            }
            span.jumping-char {
                font-size: 35px;
            }

            @media only screen and (max-width: 768px) {
                /* CSS styles for screens 768px or less */
                .step-wrappers .step-wrapper{
                    max-width: 100% !important;
                }
            }
            
        </style>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.3.3/html2canvas.min.js"></script>

        <script>
        function captureDivToImageAndOpenInNewTab(divId) {
            // Get the div element to capture
            const divToCapture = document.getElementById(divId);
            console.log(divToCapture)
            // Use html2canvas to capture the div and create a canvas element
            const loadingElement = document.getElementById("loading");
            loadingElement.style.display = "block";
            html2canvas(divToCapture).then((canvas) => {
            // Convert the canvas to a data URL
            const dataUrl = canvas.toDataURL("image/png");
            //   console.log(dataUrl)
            // Create a new window and add the image to it
            const printWindow = window.open("", "_blank");
            printWindow.document.write(`<html><head><title>Mã vận đơn</title></head><body><img src="`+dataUrl+`" onload="window.print();"></body></html>`);
            printWindow.document.close();
            // Hide the loading element
            loadingElement.style.display = "none";
            });
        }
        </script>
        <div id="loading" class="loading-container" style="display: none;">
        <div class="loading-text">
            <span class="jumping-char">Đ</span>
            <span class="jumping-char">A</span>
            <span class="jumping-char">N</span>
            <span class="jumping-char">G</span>
            <span class="jumping-char">T</span>
            <span class="jumping-char">Ả</span>
            <span class="jumping-char">I</span>
            <span class="jumping-char">.</span>
            <span class="jumping-char">.</span>
            <span class="jumping-char">.</span>
        </div>
        </div>
        EOT;

        foreach($delivery_datas as $delivery_data){
            $trackinfos = $delivery_data['origin_info']['trackinfo']; 
            $trackinfos = array_reverse($trackinfos);

            $status_vals = [
                "pending" => "Gói mới được thêm vào đang chờ theo dõi",
                "notfound" => "Thông tin theo dõi gói hàng vẫn chưa có sẵn",
                "transit" => "Chuyển phát nhanh đã nhận gói hàng từ người giao hàng, gói hàng đang trên đường đến điểm đến",
                "pickup" => "Còn được gọi là 'Out For Delivery', người chuyển phát nhanh sắp giao gói hàng hoặc gói hàng đang chờ người nhận đến nhận",
                "delivered" => "Gói hàng đã được giao thành công",
                "expired" => "Không có thông tin theo dõi trong 30 ngày đối với dịch vụ chuyển phát nhanh hoặc không có thông tin theo dõi trong 60 ngày đối với dịch vụ bưu điện kể từ khi gói được thêm vào",
                "undelivered" => "Còn được gọi là 'Không thành công', người chuyển phát nhanh đã cố gắng giao hàng nhưng không thành công, thường để lại thông báo và sẽ cố gắng giao hàng lại",
                "exception" => "Gói hàng bị bỏ lỡ, người nhận địa chỉ trả lại gói hàng cho người gửi hoặc các trường hợp ngoại lệ khác",
                "InfoReceived" => "Người vận chuyển đã nhận được yêu cầu từ người gửi hàng và sắp nhận hàng"
            
            ];

            $checkpoint_delivery_substatus = [
                "delivered001"	=> "Gói hàng đã được giao thành công",
                "delivered002"	=> "Gói hàng đã được người nhận lấy đi",
                "delivered003"	=> "Gói hàng đã được người nhận ký nhận và nhận hàng", 
                "delivered004"	=> "Gói hàng đã được để tại cửa trước hoặc giao cho hàng xóm của bạn",
                "transit001" =>	"Gói hàng đang trên đường đi đến điểm đích",
                "transit002" =>	"Gói hàng đã đến trung tâm phân loại hoặc trung tâm trung chuyển",
                "transit003" =>	"Gói hàng đã đến cơ sở giao hàng",
                "transit004" =>	"Gói hàng đã đến quốc gia nhận hàng",
                "transit005" =>	"Hải quan đã hoàn tất thủ tục",
                "transit006" =>	"Hàng đã được chuyển đi",
                "transit007" =>	"Khởi hành từ sân bay"
            
            ];

            $checkpoint_delivery_status = [
                "delivered001"	=> "Gói hàng đã được giao thành công",
                "delivered002"	=> "Gói hàng đã được người nhận lấy đi",
                "delivered003"	=> "Gói hàng đã được người nhận ký nhận và nhận hàng", 
                "delivered004"	=> "Gói hàng đã được để tại cửa trước hoặc giao cho hàng xóm của bạn"
            
            ];


            $khongtimthayvandon = false;
            $danhsachvanchuyen = [];
            $madonvivanchuyen = false;
            $diachinhanhang = false;
            $company = false;
            $contact_email = false;
            $check_order_json = false;

            $total_progressbar_steps = count($trackinfos);
            $progress_percentant =  round (100/ $total_progressbar_steps);
            $total_progressbar_steps_html = "";
            $detail_progressbar_steps_html = "";

            if ($total_progressbar_steps == 1){
                $img = "<div class='progress-image-wrapper'>
                    <img src='https://portal.chantroituonglai.com/assets/images/CJ-2-trans.png' class='img'/>
                </div>";
            } else  if ($total_progressbar_steps == 2){
                $img = "<div class='progress-image-wrapper'>
                    <img src='https://portal.chantroituonglai.com/assets/images/CJ-3-trans.png' class='img'/>
                </div>";
            } else  if ($total_progressbar_steps == 3){
                $img = "<div class='progress-image-wrapper'>
                    <img src='https://portal.chantroituonglai.com/assets/images/CJ-4-trans.png' class='img'/>
                </div>";
            } else  if ($total_progressbar_steps == 4){
                $img = "<div class='progress-image-wrapper'>
                    <img src='https://portal.chantroituonglai.com/assets/images/CJ-5-trans.png' class='img'/>
                </div>";
            } else  if ($total_progressbar_steps == 5){
                $img = "<div class='progress-image-wrapper'>
                    <img src='https://portal.chantroituonglai.com/assets/images/CJ-6-trans.png' class='img'/>
                </div>";
            } else  if ( ($total_progressbar_steps == 6 || $total_progressbar_steps = 7) && $delivery_data['delivery_status'] == 'delivered'){
                $img = "<div class='progress-image-wrapper'>
                    <img src='https://portal.chantroituonglai.com/assets/images/CJ-7-trans.png' class='img'/>
                </div>";
            } 

            if ($donvivanchuyen != 1){
                $img = "";
            }
            $tinhtranggiaohang = '<p><b>Tình trạng giao hàng/Delivery Status: </b><span style="color: red; font-weight:bold">'. $status_vals[$delivery_data['delivery_status']]. '</span></p>';
            $count_step = 1;
           
            foreach ($trackinfos as $trackinfo ){ 
                
                $total_progressbar_steps_html .= ' <li style="list-style-type: none;
                font-size: 13px;
                float: left;
                position: relative;
                font-weight: 400; width: '.$progress_percentant.'%;"></li>';
                

                $date = new DateTime($trackinfo['checkpoint_date'], new DateTimeZone('UTC'));
                $date->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'));
                $checkpoint_date = $date->format('h:s d/m/Y' );

                $detail_progressbar_steps_html .= '  <div class="step-wrapper" style="
                flex-grow: 1;
                max-width: calc(30% - 10px);
                box-shadow: rgba(149, 157, 165, 0.2) 0px 8px 24px;
                margin: 10px;
                padding: 8px;
                border-radius: 4px;
                position: relative;
                padding-top: 45px;
                background: _url(https://winboldlogistics.com/wp-content/uploads/2022/11/Logo-winbold.jpg);
                background-size: 50px;
                background-repeat: no-repeat;
                background-position-x: 95%;
                background-position-y: 10px;
                opacity: 1;
                ">
                   <span class="step-number" style="font-size: 20px;
                   font-weight: bold;
                   color: white;
                   position: absolute;
                   left: -10px;
                   top: 0px;
                   background: #017bff;
                   padding: 4px 6px;
                   border-radius: 4px;">Trạm '.$count_step.'</span>
                    <div style="display: flex; flex-direction: column;">
                        <p style="font-weight: 700;">🛬 '.$status_vals[$trackinfo['checkpoint_delivery_status']] .'</p>
                        <p>🗓 Thời gian (Giờ Việt Nam): '.$checkpoint_date.'</p>
                        <p>📍 Địa điểm: '.$trackinfo['location'].'</p>
                        <p>📔 Thông tin khác: <small>'.  $checkpoint_delivery_substatus[$trackinfo['checkpoint_delivery_substatus']] .'</small> </p>
                        <box>🔎 <a target="_blank" href="https://translate.google.com/?sl=ko&tl=vi&text='.urlencode($trackinfo['tracking_detail']).'&op=translate">Bấm để dịch: '.  $trackinfo['tracking_detail'].'</a></box>
                    </div>
                </div>';
                // $message['type'] = 'text';
                // $message['text'] =  $index . '|' . $trackinfo['checkpoint_delivery_substatus']. '|' .  $trackinfo['location'] . '|' . $status_vals[$trackinfo['checkpoint_delivery_status']] . '|CHECKPOINT: ' . $trackinfo['checkpoint_date'] . '|' . $trackinfo['tracking_detail'];
                // $messages[] = $message;
                $count_step++;
            }

            // var_dump
            // ob_start();
            // var_dump($delivery_datas);
            // $debug_dump = ob_get_clean();

            $tracking_number = $delivery_data["tracking_number"];
            $created_at = $delivery_data["created_at"];
            // $timestamp = '2023-04-18T16:20:06+00:00';
            $date = new DateTime($created_at, new DateTimeZone('UTC'));
            $date->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'));
            $ngaytao = $date->format('h:s d/m/Y' );
            $html .= <<<EOT
          
            <div style="max-width: 100%; margin: 0 auto; padding: 1rem 0.25rem 5rem 0.25rem;" data-trackingid="$tracking_number" id="$tracking_number">
                <div
                    style="position: relative;display: flex;flex-direction: column;min-width: 0;word-wrap: break-word;/* background-color: #fff; */background-clip: border-box;border: 1px solid rgba(0,0,0,.125);border-radius: .25rem;padding: 25px 15px;background: _url(https://winboldlogistics.com/wp-content/uploads/2022/11/Logo-winbold.jpg);background-size: 100px 100px;background-repeat: no-repeat;background-color: rgb(242 242 242 / 62%);background-position-x: 97%;background-position-y: 25px;opacity: 1;">
                    <div style="display: flex;flex-wrap: wrap;justify-content: space-between;padding: 0;">
                        <div style="display: flex;">
                            <h5 class="tracking-tag">MÃ VẬN ĐƠN: <span style="color: #007bff; font-weight: 700;">#$tracking_number</span></h5>
                            <a style="cursor:pointer; padding-left:5px;" onclick="captureDivToImageAndOpenInNewTab('$tracking_number'); return false;"> 🖨 In Vận Đơn</a>
                        </div>
                        <div style="display: flex; flex-direction: column; text-align: right; font-size: 0.875rem;">
                            <p style="margin-bottom: 0;">Ngày Tra cứu: <span>$ngaytao</span></p>
                        </div>
                    </div>
                    <div class="otherstatuses">
                        <h4> $tinhtranggiaohang</h4>
                    </div>
                    $img 
                    <!-- Add class 'active' to progress -->
                    <!--div style="display: flex; flex-wrap: wrap; justify-content: center;">
                        <div style="width: 100%;">
                            <ul id="progressbar"
                                style=" margin-bottom: 30px;
                                overflow: hidden;
                                color: #455A64;
                                padding-left: 0px;
                                margin-top: 30px;">
                                $total_progressbar_steps_html
                            </ul>
                        </div>
                    </div-->
                    <div class="step-wrappers" style=" display: flex;
                    flex-wrap: wrap; 
                    width: 100%;padding-top: 1rem;">
                        $detail_progressbar_steps_html
                    </div>
                </div>
                <p  style="
                float: right;
                font-size: 10px;
                color: blue;
                font-style: italic;
            ">Version 1.0.2 - Powered by <a target="_blank" href="https://chantroituonglai.com">Future Horizon Global Tracking</a></p>
            </div>
            EOT;
        }
     
        return $html;
    }

    // Form tra cuu
    function formtracuuvandon(){
        $data = array();
        $this->data(  $data );
        $this->view('vandon/formtracuu');
        $this->layout();
    }

    // API Ngân hàng dữ liệu
    function get_sharepoint_link_mapping(){
        $this->load->model('external_model');
        $result = $this->external_model->get_record($this->input->post('uniquekey'));
        echo json_encode(
            array(
                'response' => $result != null ? $result : false,
                '_post' => $this->input->post('body')
            )
        );
    }

    function update_sharepoint_link_mapping(){
        $this->load->model('external_model');
        $result = $this->external_model->add_record(array(
            "uniquekey" => $this->input->post('uniquekey'),
            "rel" => $this->input->post('rel'),
            "root_id" => $this->input->post('root_id'),
            "target_id" => $this->input->post('target_id'),
            "data" => $this->input->post('data'),
        ));

        echo json_encode(
            array(
                'response' => $result,
                '_post' => $this->input->post()
            )
        );
    }


    // public function order_update_record(){
    //     $this->load->model('external_model');
    //     $result = $this->external_model->update_record($this->input->post('uniquekey'), array(
    //         "target_id" => $this->input->post('target_id'),
    //     ));

    //     echo json_encode(
    //         array(
    //             'response' => $result,
    //             '_post' => $this->input->post()
    //         )
    //     );
    // }


    /** ----------------------------------------------------------------
     *  GET /apis/lotte/orders
     *  @query  limit   int   default 25
     *  @query  offset  int   default 0
     * ---------------------------------------------------------------*/
    public function lotte_orders()
    {
        $this->load->model('external_model');
        $limit  = (int) $this->input->get('limit')  ?: 25;
        $offset = (int) $this->input->get('offset') ?: 0;

        $rows   = $this->external_model->get_lotte_orders([
            'limit'  => $limit,
            'offset' => $offset,
        ]);

        echo json_encode([
            'total' => count($rows),
            'rows'  => $rows,
        ]);
    }

    /** ---------------------------------------------------------------
     *  GET /apis/lotte/order/{id}
     * ---------------------------------------------------------------*/
    public function lotte_order($id = 0)
    {
        $this->load->model('external_model');
        $row = $this->external_model->get_record_by_id($id);
        if (!$row || strpos($row->uniquekey, 'LOTTE') !== 0) {
            $this->output->set_status_header(404);
            echo json_encode(['error' => 'Order not found']);
            return;
        }
        echo json_encode($row);
    }

    /** ---------------------------------------------------------------
     *  DELETE /apis/lotte_clear/{id}
     * ---------------------------------------------------------------*/
    public function lotte_clear($id = 0)
    {
        // Add CORS headers to allow cross-origin requests
        header('Access-Control-Allow-Origin: https://automate.moriitalia.vn');
        header('Access-Control-Allow-Methods: DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        
        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        $this->load->model('external_model');
        if ($this->input->server('REQUEST_METHOD') !== 'DELETE') {
            $this->output->set_status_header(405);
            echo json_encode(['error' => 'Method Not Allowed']); return;
        }

        $newKey = $this->external_model->trash_lotte_order((int)$id);

        if ($newKey) {
            echo json_encode(['success' => true, 'new_uniquekey' => $newKey]);
        } else {
            $this->output->set_status_header(404);
            echo json_encode(['error' => 'Order not found or cannot rename']);
        }
    }

    /** ----------------------------------------------------------------
     *  GET /apis/bigc/orders
     *  @query  limit   int   default 25
     *  @query  offset  int   default 0
     * ---------------------------------------------------------------*/
    public function bigc_orders()
    {
        $this->load->model('external_model');
        $limit  = (int) $this->input->get('limit')  ?: 25;
        $offset = (int) $this->input->get('offset') ?: 0;

        $rows   = $this->external_model->get_bigc_orders([
            'limit'  => $limit,
            'offset' => $offset,
        ]);

        echo json_encode([
            'total' => count($rows),
            'rows'  => $rows,
        ]);
    }

    /** ---------------------------------------------------------------
     *  GET /apis/bigc/order/{id}
     * ---------------------------------------------------------------*/
    public function bigc_order($id = 0)
    {
        $this->load->model('external_model');
        $row = $this->external_model->get_record_by_id($id);
        if (!$row || strpos($row->uniquekey, 'BIGC_PO_') !== 0) {
            $this->output->set_status_header(404);
            echo json_encode(['error' => 'Order not found']);
            return;
        }
        echo json_encode($row);
    }

    public function bigc_clear($id = 0)
    {
           $this->load->model('external_model');
        if ($this->input->server('REQUEST_METHOD') !== 'DELETE') {
            $this->output->set_status_header(405);
            echo json_encode(['error' => 'Method Not Allowed']); return;
        }

        $newKey = $this->external_model->trash_bigc_order((int)$id);

        if ($newKey) {
            echo json_encode(['success' => true, 'new_uniquekey' => $newKey]);
        } else {
            $this->output->set_status_header(404);
            echo json_encode(['error' => 'Order not found or cannot rename']);
        }
    }

    /** ----------------------------------------------------------------
     *  GET /apis/coop_orders
     *  @query  limit   int   default 25
     *  @query  offset  int   default 0
     * ---------------------------------------------------------------*/
    public function coop_orders()
    {
        $this->load->model('external_model');
        $limit  = (int) $this->input->get('limit')  ?: 25;
        $offset = (int) $this->input->get('offset') ?: 0;

        $rows   = $this->external_model->get_coop_orders([
            'limit'  => $limit,
            'offset' => $offset,
        ]);

        echo json_encode([
            'total' => count($rows),
            'rows'  => $rows,
        ]);
    }

    /** ---------------------------------------------------------------
     *  GET /apis/coop_order/{id}
     * ---------------------------------------------------------------*/
    public function coop_order($id = 0)
    {
        $this->load->model('external_model');
        $row = $this->external_model->get_record_by_id($id);
        if (!$row || strpos($row->uniquekey, 'B2BCOOPMART') !== 0) {
            $this->output->set_status_header(404);
            echo json_encode(['error' => 'Order not found']);
            return;
        }
        echo json_encode($row);
    }

    /** ---------------------------------------------------------------
     *  DELETE /apis/coop_order/{id}/clear
     * ---------------------------------------------------------------*/
    public function coop_clear($id = 0)
    {
        $this->load->model('external_model');
        // if ($this->input->server('REQUEST_METHOD') !== 'DELETE') {
        //     $this->output->set_status_header(405);
        //     echo json_encode(['error' => 'Method Not Allowed']); return;
        // }

        $newKey = $this->external_model->trash_coop_order((int)$id);

        if ($newKey) {
            echo json_encode(['success' => true, 'new_uniquekey' => $newKey]);
        } else {
            $this->output->set_status_header(404);
            echo json_encode(['error' => 'Order not found or cannot rename']);
        }
    }

    /** ---------------------------------------------------------------
     *  DELETE /apis/email_clear/{id}
     *  Soft delete an email order by changing its uniquekey
     * ---------------------------------------------------------------*/
    public function email_clear($id = 0)
    {
        Header('Access-Control-Allow-Origin: *'); //for allow any domain, insecure
        Header('Access-Control-Allow-Headers: *'); //for allow any headers, insecure
        Header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE'); //method allowed

        $this->load->model('external_model');
        // if ($this->input->server('REQUEST_METHOD') !== 'DELETE') {
        //     $this->output->set_status_header(405);
        //     echo json_encode(['error' => 'Method Not Allowed']); 
        //     return;
        // }

        $newKey = $this->external_model->trash_email_order((int)$id);

        if ($newKey) {
            echo json_encode(['success' => true, 'new_uniquekey' => $newKey]);
        } else {
            $this->output->set_status_header(404);
            echo json_encode(['error' => 'Email order not found or cannot rename']);
        }
    }

    /** ---------------------------------------------------------------
     *  POST /apis/coop_scan
     *  Re-scan COOP orders and try to map them to Haravan orders
     * ---------------------------------------------------------------*/
    public function coop_scan()
    {
        $this->load->model('external_model');
        
        // Get batch size and offset from request
        $batch_size = (int) $this->input->post('batch_size') ?: 25; // Process 25 orders at a time
        $offset = (int) $this->input->post('offset') ?: 0;
        $total_limit = (int) $this->input->post('total_limit') ?: 100; // Limit to 100 most recent orders
        
        // Get unmapped COOP orders from the last 7 days (with empty or invalid target_id)
        $seven_days_ago = date('Y-m-d H:i:s', strtotime('-7 days'));
        
        $unmappedOrders = $this->db->select('*')
            ->from(db_prefix().'external_data_mapping')
            ->where('rel', 'Order') // Only include orders that have been scanned (not EMAILORDER)
            ->where('dateadded >=', $seven_days_ago) // Only orders from last 7 days
            ->group_start()
                ->like('uniquekey', 'B2BCOOPMART_', 'after')
                ->or_like('uniquekey', 'COOPMART_', 'after')
            ->group_end()
            ->group_start()
                ->where('target_id', '')
                ->or_where('target_id', '""')
                ->or_where('target_id', "''")
                ->or_where('target_id', '0')
                ->or_where('target_id', 0)
                ->or_where('target_id IS NULL', null, false)
            ->group_end()
            ->order_by('dateadded', 'desc')
            ->limit($batch_size, $offset)
            ->get()->result_array();
            
        // Get total count for progress tracking
        $total_count = $this->db->select('COUNT(*) as count')
            ->from(db_prefix().'external_data_mapping')
            ->where('rel', 'Order') // Only include orders that have been scanned (not EMAILORDER)
            ->where('dateadded >=', $seven_days_ago) // Only orders from last 7 days
            ->group_start()
                ->like('uniquekey', 'B2BCOOPMART_', 'after')
                ->or_like('uniquekey', 'COOPMART_', 'after')
            ->group_end()
            ->group_start()
                ->where('target_id', '')
                ->or_where('target_id', '""')
                ->or_where('target_id', "''")
                ->or_where('target_id', '0')
                ->or_where('target_id', 0)
                ->or_where('target_id IS NULL', null, false)
            ->group_end()
            ->order_by('dateadded', 'desc')
            ->limit($total_limit)
            ->get()->row()->count;
            
        if (!$unmappedOrders) {
            return $this->output->set_output(json_encode([
                'success' => false,
                'message' => 'Không tìm thấy đơn COOP nào cần map',
                'mapped' => 0,
                'total' => $total_count,
                'offset' => $offset,
                'batch_size' => $batch_size,
                'done' => true
            ]));
        }
        
        // Fetch recent Haravan orders
        $hOrders = $this->fetch_haravan_orders(200); // Get 200 most recent orders
        if (!$hOrders) {
            return $this->output->set_status_header(500)
                ->set_output(json_encode([
                    'success' => false,
                    'error' => 'Không lấy được đơn Haravan',
                    'mapped' => 0,
                    'total' => $total_count,
                    'offset' => $offset,
                    'batch_size' => $batch_size,
                    'done' => false
                ]));
        }
        
        // Create mapping for quick lookup
        $mapHv = [];
        // Debug - log đơn hàng Haravan để kiểm tra
        // log_message('error', 'COOP scan: Total Haravan orders fetched: ' . count($hOrders));
        if (count($hOrders) > 0) {
            // log_message('error', 'COOP scan: First Haravan order sample: ' . json_encode($hOrders[0]));
        }
        
        foreach ($hOrders as $o) {
            // First priority: Check name for B2BCOOPMART or COOPMART pattern
            if (!empty($o->name)) {
                // Log để debug
                // log_message('error', 'COOP scan: Processing Haravan order: ' . $o->name . ' (ID: ' . $o->id . ')');
                
                // Look for B2BCOOPMART_XXXXXXXX-XX pattern in name
                if (preg_match('/B2BCOOPMART_(\d+-\d+)/i', $o->name, $m)) {
                    // log_message('error', 'COOP scan: Matched B2BCOOPMART_ pattern: ' . $m[1]);
                    $mapHv[$m[1]] = $o->id;
                    
                    // Thêm mới: Map theo ID không có dấu gạch ngang
                    $clean_id = str_replace('-', '', $m[1]);
                    $mapHv[$clean_id] = $o->id;
                    continue;
                }
                
                // Look for COOPMART_ pattern
                if (preg_match('/COOPMART_([A-Za-z0-9_-]+)/i', $o->name, $m)) {
                    // log_message('error', 'COOP scan: Matched COOPMART_ pattern: ' . $m[1]);
                    $mapHv[$m[1]] = $o->id;
                    
                    // Thêm mới: Map theo ID không có dấu gạch ngang
                    $clean_id = str_replace('-', '', $m[1]);
                    $mapHv[$clean_id] = $o->id;
                    continue;
                }
                
                // Also check for just the order number without prefix
                if (preg_match('/(\d+-\d+)/', $o->name, $m)) {
                    // log_message('error', 'COOP scan: Matched number pattern: ' . $m[1]);
                    $mapHv[$m[1]] = $o->id;
                    
                    // Thêm mới: Map theo ID không có dấu gạch ngang
                    $clean_id = str_replace('-', '', $m[1]);
                    $mapHv[$clean_id] = $o->id;
                }
                
                // Thêm mới: Tìm kiếm số đơn hàng trực tiếp trong name
                foreach ($unmappedOrders as $coop_order) {
                    $rootId = $coop_order['root_id'];
                    if (strpos($o->name, $rootId) !== false) {
                        log_message('error', 'COOP scan: Direct match in name for: ' . $rootId);
                        $mapHv[$rootId] = $o->id;
                        
                        // Thêm version không có dấu gạch ngang
                        $clean_root = str_replace('-', '', $rootId);
                        $mapHv[$clean_root] = $o->id;
                    }
                }
            }
            
            // Second priority: Check ref_order_number
            if (!empty($o->ref_order_number)) {
                // log_message('error', 'COOP scan: Ref order number: ' . $o->ref_order_number);
                $mapHv[$o->ref_order_number] = $o->id;
                
                // Thêm mới: Map theo ID không có dấu gạch ngang
                $clean_ref = str_replace('-', '', $o->ref_order_number);
                $mapHv[$clean_ref] = $o->id;
            }
        }
        
        // Log map đã tạo
        // log_message('error', 'COOP scan: Map created: ' . json_encode($mapHv));
        
        // Update COOP orders with matching Haravan IDs
        $updated = 0;
        $mappedOrders = [];
        
        foreach ($unmappedOrders as $order) {
            $rootId = $order['root_id'];
            $uniquekey = $order['uniquekey'];
            
            // Check if we have a direct match by root_id
            if (isset($mapHv[$rootId])) {
                // Found a match by root_id
                if ($this->external_model->update_record_with_validation(
                    $uniquekey,
                    ['target_id' => $mapHv[$rootId]]
                )) {
                    $updated++;
                    $mappedOrders[] = [
                        'id' => $order['id'],
                        'uniquekey' => $uniquekey,
                        'root_id' => $rootId,
                        'target_id' => $mapHv[$rootId]
                    ];
                }
            } else {
                // Try alternative: check if uniquekey appears in any Haravan order name
                foreach ($hOrders as $ho) {
                    if (!empty($ho->name) && 
                        (strpos($ho->name, $uniquekey) !== false || 
                         strpos($ho->name, $rootId) !== false)) {
                        
                        if ($this->external_model->update_record_with_validation(
                            $uniquekey,
                            ['target_id' => $ho->id]
                        )) {
                            $updated++;
                            $mappedOrders[] = [
                                'id' => $order['id'],
                                'uniquekey' => $uniquekey,
                                'root_id' => $rootId,
                                'target_id' => $ho->id
                            ];
                            break;
                        }
                    }
                }
            }
        }
        
        // Calculate next offset and check if we're done
        $next_offset = $offset + $batch_size;
        $is_done = $next_offset >= $total_count || $next_offset >= $total_limit;
        
        return $this->output->set_output(json_encode([
            'success' => true,
            'total' => $total_count,
            'offset' => $offset,
            'next_offset' => $is_done ? null : $next_offset,
            'batch_size' => $batch_size,
            'mapped' => $updated,
            'done' => $is_done,
            'progress' => min(100, round(($next_offset / min($total_count, $total_limit)) * 100)),
            'message' => 'Đã quét và map được ' . $updated . '/' . count($unmappedOrders) . ' đơn hàng (Batch: ' . ($offset + 1) . '-' . $next_offset . '/' . min($total_count, $total_limit) . ')',
            'mapped_orders' => $mappedOrders
        ]));
    }

   /* ------------------------------------------------------------------
    *  /apis/bigc/fix_target_id
    *  Dò 500 đơn Haravan trong tháng hiện tại,
    *  gán lại target_id cho Big-C mapping còn trống.
    * ------------------------------------------------------------------ */
    public function fix_target_id()
    {
        $this->load->model('external_model');

        /* 1. Lấy các record Big C chưa có target_id --------------------- */
        $needFix = $this->db->select('*')
            ->from(db_prefix().'external_data_mapping')
            ->like('uniquekey', 'BIGC_PO_', 'after')
            ->group_start()
                ->where('target_id', '')
                ->or_where('target_id', '""')
                ->or_where('target_id', "''")
            ->group_end()
            ->order_by('dateadded', 'desc')
            ->limit(200)
            ->get()->result_array();

        if (!$needFix) {
            return $this->output->set_output(json_encode([
                'updated' => 0, 'message' => 'Không có bản ghi cần sửa'
            ]));
        }

        /* 2. Tải 500 orders Haravan (tháng hiện tại) -------------------- */
        $orders = $this->fetch_haravan_orders(500);   // hàm này đã giới hạn tháng
        if (!$orders) {
            return $this->output->set_status_header(500)
                ->set_output(json_encode(['error'=>'Không lấy được đơn Haravan']));
        }

        /* ---- 2a. Tạo MAP nhanh --------------------------------------- */
        $mapHv = [];                // root_id  -> haravan_order_id
        foreach ($orders as $o) {

            // Ưu tiên ref_order_number (nếu có)
            if (!empty($o->ref_order_number)) {
                $mapHv[$o->ref_order_number] = $o->id;
                continue;
            }

            // Nếu không có -> cố bóc root_id từ field name
            // Ví dụ name = "B2BBIGC_2509047099047" hoặc "... 2509047099047 ..."
            if (!empty($o->name) && preg_match('/(\d{10,})/', $o->name, $m)) {
                $mapHv[$m[1]] = $o->id;              // $m[1] = root_id dạng số
            }
        }

        /* 3. Gán lại target_id cho bảng Big C --------------------------- */
        $updated = 0;
        foreach ($needFix as $r) {
            $root = $r['root_id'];                   // vd: 2509047099047
            if (isset($mapHv[$root])) {
                if ($this->external_model->update_record(
                        $r['uniquekey'],
                        ['target_id' => $mapHv[$root]])) {
                    $updated++;
                }
            } else {
                // fallback: thử so chuỗi uniquekey trong name
                foreach ($orders as $o) {
                    if (!empty($o->name) &&
                        strpos($o->name, $r['uniquekey']) !== false) {
                        if ($this->external_model->update_record(
                                $r['uniquekey'], ['target_id'=>$o->id])) {
                            $updated++; break;
                        }
                    }
                }
            }
        }

        return $this->output->set_output(json_encode([
            'total_need_fix' => count($needFix),
            'updated'        => $updated,
            'message'        => 'Đã dò & gán target_id theo ref_order_number / name'
        ]));
    }

    public function fix_target_id_coop()
    {
        $this->load->model('external_model');

        /* 1. Lấy các record COOP chưa có target_id --------------------- */
        $needFix = $this->db->select('*')
            ->from(db_prefix().'external_data_mapping')
            ->like('uniquekey', 'B2BCOOPMART', 'after')
            ->group_start()
                ->where('target_id', '')
                ->or_where('target_id', '""')
                ->or_where('target_id', "''")
                ->or_where('target_id', "0")
            ->group_end()
            ->order_by('dateadded', 'desc')
            ->limit(200)
            ->get()->result_array();

        if (!$needFix) {
            return $this->output->set_output(json_encode([
                'updated' => 0, 'message' => 'Không có bản ghi cần sửa'
            ]));
        }

        /* 2. Tải 500 orders Haravan (tháng hiện tại) -------------------- */
        $orders = $this->fetch_haravan_orders(500);   // hàm này đã giới hạn tháng
        if (!$orders) {
            return $this->output->set_status_header(500)
                ->set_output(json_encode(['error'=>'Không lấy được đơn Haravan']));
        }

        /* ---- 2a. Tạo MAP nhanh --------------------------------------- */
        $mapHv = [];                // root_id  -> haravan_order_id
        foreach ($orders as $o) {
            // Ưu tiên ref_order_number (nếu có)
            if (!empty($o->ref_order_number)) {
                $mapHv[$o->ref_order_number] = $o->id;
                continue;
            }

            // Nếu không có -> cố bóc root_id từ field name
            // Ví dụ name = "B2BCOOPMART_93192432-00" hoặc "... 93192432-00 ..."
            if (!empty($o->name) && preg_match('/(\d+-\d+)/', $o->name, $m)) {
                $mapHv[$m[1]] = $o->id;              // $m[1] = root_id dạng số-số
            }
        }

        /* 3. Gán lại target_id cho bảng COOP --------------------------- */
        $updated = 0;
        foreach ($needFix as $r) {
            $root = $r['root_id'];                   // vd: 93192432-00
            if (isset($mapHv[$root])) {
                if ($this->external_model->update_record(
                        $r['uniquekey'],
                        ['target_id' => $mapHv[$root]])) {
                    $updated++;
                }
            } else {
                // fallback: thử so chuỗi uniquekey trong name
                foreach ($orders as $o) {
                    if (!empty($o->name) &&
                        strpos($o->name, $r['uniquekey']) !== false) {
                        if ($this->external_model->update_record(
                                $r['uniquekey'], ['target_id'=>$o->id])) {
                            $updated++; break;
                        }
                    }
                }
            }
        }

        return $this->output->set_output(json_encode([
            'total_need_fix' => count($needFix),
            'updated'        => $updated,
            'message'        => 'Đã dò & gán target_id theo ref_order_number / name'
        ]));
    }
    
    public function fix_target_id_lotte()
    {
        $this->load->model('external_model');

        /* 1. Lấy các record LOTTE chưa có target_id --------------------- */
        $needFix = $this->db->select('*')
            ->from(db_prefix().'external_data_mapping')
            ->like('uniquekey', 'LOTTE', 'after')
            ->group_start()
                ->where('target_id', '')
                ->or_where('target_id', '""')
                ->or_where('target_id', "''")
                ->or_where('target_id', "0")
            ->group_end()
            ->order_by('dateadded', 'desc')
            ->limit(200)
            ->get()->result_array();

        if (!$needFix) {
            return $this->output->set_output(json_encode([
                'updated' => 0, 'message' => 'Không có bản ghi Lotte cần sửa'
            ]));
        }

        /* 2. Tải 500 orders Haravan (tháng hiện tại) -------------------- */
        $orders = $this->fetch_haravan_orders(500);   // hàm này đã giới hạn tháng
        if (!$orders) {
            return $this->output->set_status_header(500)
                ->set_output(json_encode(['error'=>'Không lấy được đơn Haravan']));
        }

        /* ---- 2a. Tạo MAP nhanh --------------------------------------- */
        $mapHv = [];                // root_id  -> haravan_order_id
        foreach ($orders as $o) {
            // Ưu tiên ref_order_number (nếu có)
            if (!empty($o->ref_order_number)) {
                $mapHv[$o->ref_order_number] = $o->id;
                
                // Also map without B2B prefix if it exists
                if (strpos($o->ref_order_number, 'B2B') === 0) {
                    $withoutPrefix = substr($o->ref_order_number, 3);
                    $mapHv[$withoutPrefix] = $o->id;
                }
                continue;
            }

            // Kiểm tra note_attributes cho LOTTE_PO
            if (isset($o->note_attributes) && is_array($o->note_attributes)) {
                foreach ($o->note_attributes as $attr) {
                    if ($attr->name === 'LOTTE_PO' && !empty($attr->value)) {
                        $mapHv[$attr->value] = $o->id;
                        
                        // Also map without B2B prefix if it exists
                        if (strpos($attr->value, 'B2B') === 0) {
                            $withoutPrefix = substr($attr->value, 3);
                            $mapHv[$withoutPrefix] = $o->id;
                        }
                        break;
                    }
                }
            }

            // Nếu không có -> cố bóc root_id từ field name
            // Check for both LOTTE\d+ and B2BLOTTE\d+ patterns
            if (!empty($o->name)) {
                // Check for LOTTE(\d+)
                if (preg_match('/LOTTE(\d+)/', $o->name, $m)) {
                    $mapHv[$m[1]] = $o->id;
                }
                
                // Check for B2B(\d+)
                if (preg_match('/B2B(\d+)/', $o->name, $m)) {
                    $mapHv[$m[1]] = $o->id;
                }
            }
        }

        /* 3. Gán lại target_id cho bảng LOTTE --------------------------- */
        $updated = 0;
        $mapped_orders = [];
        foreach ($needFix as $r) {
            $root = $r['root_id'];
            if (isset($mapHv[$root])) {
                if ($this->external_model->update_record(
                        $r['uniquekey'],
                        ['target_id' => $mapHv[$root]])) {
                    $updated++;
                    
                    // Store mapping details
                    $mapped_orders[] = [
                        'id' => $r['id'],
                        'uniquekey' => $r['uniquekey'],
                        'haravan_id' => $mapHv[$root]
                    ];
                }
            } else {
                // fallback: thử so chuỗi uniquekey trong name
                foreach ($orders as $o) {
                    if (!empty($o->name) &&
                        strpos($o->name, $r['uniquekey']) !== false) {
                        if ($this->external_model->update_record(
                                $r['uniquekey'], ['target_id'=>$o->id])) {
                            $updated++;
                            
                            // Store mapping details
                            $mapped_orders[] = [
                                'id' => $r['id'],
                                'uniquekey' => $r['uniquekey'],
                                'haravan_id' => $o->id
                            ];
                            break;
                        }
                    }
                }
            }
        }

        return $this->output->set_output(json_encode([
            'total_need_fix' => count($needFix),
            'updated'        => $updated,
            'message'        => 'Đã dò & gán target_id theo ref_order_number / name / note_attributes',
            'mapped_orders'  => $mapped_orders
        ]));
    }
    
    /**
     * Hàm debug kiểm tra mapping đơn hàng Lotte với Haravan
     */
    public function debug_lotte_mapping()
    {
        $this->load->model('external_model');
        
        // Kiểm tra cụ thể với đơn hàng problematic
        $order_id = $this->input->get('order_id') ?? '2505200100100220';
        
        // Tìm bản ghi trong database
        $lotte_record = $this->db->select('*')
            ->from(db_prefix().'external_data_mapping')
            ->like('uniquekey', 'LOTTE')
            ->like('root_id', $order_id)
            ->get()->row_array();
            
        // Lấy 100 đơn Haravan gần nhất để tìm match
        $orders = $this->fetch_haravan_orders(100);
        
        // Kết quả tìm kiếm
        $found_matches = [];
        $possible_matches = [];
        
        foreach ($orders as $o) {
            // Kiểm tra ref_order_number
            if (!empty($o->ref_order_number)) {
                if ($o->ref_order_number == $order_id || $o->ref_order_number == 'B2B'.$order_id) {
                    $found_matches['ref_order_number'] = [
                        'haravan_id' => $o->id,
                        'haravan_name' => $o->name,
                        'match_value' => $o->ref_order_number
                    ];
                }
            }
            
            // Kiểm tra note attributes
            if (isset($o->note_attributes) && is_array($o->note_attributes)) {
                foreach ($o->note_attributes as $attr) {
                    if ($attr->name === 'LOTTE_PO' && 
                        ($attr->value == $order_id || $attr->value == 'B2B'.$order_id)) {
                        $found_matches['note_attributes'] = [
                            'haravan_id' => $o->id,
                            'haravan_name' => $o->name,
                            'match_value' => $attr->value
                        ];
                    }
                }
            }
            
            // Kiểm tra name
            if (!empty($o->name)) {
                if (strpos($o->name, $order_id) !== false || 
                    strpos($o->name, 'B2B'.$order_id) !== false) {
                    $possible_matches[] = [
                        'haravan_id' => $o->id,
                        'haravan_name' => $o->name
                    ];
                }
            }
        }
        
        // Nếu tìm thấy match và chưa có target_id, thực hiện update
        $updated = false;
        if ($lotte_record && empty($lotte_record['target_id']) && !empty($found_matches)) {
            // Ưu tiên match theo ref_order_number
            if (isset($found_matches['ref_order_number'])) {
                $haravan_id = $found_matches['ref_order_number']['haravan_id'];
            } elseif (isset($found_matches['note_attributes'])) {
                $haravan_id = $found_matches['note_attributes']['haravan_id'];
            }
            
            if (isset($haravan_id)) {
                $updated = $this->external_model->update_record(
                    $lotte_record['uniquekey'],
                    ['target_id' => $haravan_id]
                );
            }
        }
        
        // Trả về kết quả debug
        return $this->output->set_output(json_encode([
            'lotte_record' => $lotte_record,
            'exact_matches' => $found_matches,
            'possible_matches' => $possible_matches,
            'updated' => $updated,
            'message' => $updated ? 'Đã cập nhật target_id thành công' : 'Không thể cập nhật target_id'
        ]));
    }

    /* ------------------ HÀM GỌI HARAVAN ------------------------------ */

    function fetch_haravan_orders($max = 500)
    {
        $token      = $this->haravan_token;
        $shopDomain = 'moriitaliavn.myharavan.com';              // đổi shop
        $headers    = [
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
            'Shop-Domain: ' . $shopDomain,
        ];

        /* --- Tính mốc thời gian 7 ngày gần nhất (GMT+7) ---------------- */
        $tz     = new DateTimeZone('Asia/Ho_Chi_Minh');
        $start  = (new DateTime('-7 days', $tz))
                    ->format(DateTime::ATOM);                    // ISO-8601
        $end    = (new DateTime('now', $tz))->format(DateTime::ATOM);

        $collect = [];
        $page    = 1;
        $limit   = 50;

        while (count($collect) < $max) {

            $url = 'https://apis.haravan.com/com/orders.json'
                . '?limit='      . $limit
                . '&page='       . $page
                . '&order=created_at%20desc'
                . '&created_at_min=' . rawurlencode($start);

            $resp = $this->curl_json($url, $headers);
            if (!$resp['ok']) {
                log_message('error', 'Haravan API error '. $resp['status']
                                .': '. $resp['body']);
                return false;
            }

            $json = json_decode($resp['body']);
            if (empty($json->orders)) break;

            $collect = array_merge($collect, $json->orders);
            if (count($json->orders) < $limit) break;            // hết trang
            $page++;
        }
        return array_slice($collect, 0, $max);
    }

    /* ---- tiện ích CURL trả JSON + status ---- */
    private function curl_json($url, $headers)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body   = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'ok'    => ($status >= 200 && $status < 300),
            'status'=> $status,
            'body'  => $body ?: '',
        ];
    }

    private function _search_haravan_order_by_order_name_in_range($orderName, $startIso, $endIso, $max = 500)
    {
        $needles = [$orderName];
        if ($orderName[0] !== '#') $needles[] = '#'.$orderName;

        $token      = $this->haravan_token;
        $shopDomain = 'moriitaliavn.myharavan.com';
        $headers    = [
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
            'Shop-Domain: ' . $shopDomain,
        ];

        $page    = 1;
        $limit   = 50;
        $scanned = 0;

        while ($scanned < $max) {
            $url = 'https://apis.haravan.com/com/orders.json'
                . '?limit='.$limit.'&page='.$page.'&order=created_at%20desc'
                . '&created_at_min=' . rawurlencode($startIso)
                . '&created_at_max=' . rawurlencode($endIso);

            $resp = $this->curl_json($url, $headers);
            if (!$resp['ok']) return false;

            $json = json_decode($resp['body']);
            if (empty($json->orders)) break;

            foreach ($json->orders as $o) {
                $scanned++;
                if (!empty($o->name) && in_array($o->name, $needles, true)) {
                    return ['order'=>$o, 'scanned'=>$scanned];
                }
                if ($scanned >= $max) break;
            }

            if (count($json->orders) < $limit) break;
            $page++;
        }
        return false;
    }

    private function _aeon_get_order_date($poId, $ponumber, $jsessionid)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://aeonvn.b2b.com.my/esupplier/pages/po/ExportPO',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'sbuyerOrgId=AEON_VN&spoNo=null&sstatus=null&sRelatedDocNo=null&orderDateFrom=null&orderDateTo=null&deliveryDateFrom=null&deliveryDateTo=null&receivedDateFrom=null&receivedDateTo=null&sdeliveryLocName=null&sdeliveryLoc=null&sbrandName=null&sdepartmentName=null&sSupplierCodeName=null&sSupplierCodeId=null&sortField=null&sortDirection=null&directPage=null&actionType=null&sstart=null&isExpired=null&poId='.$poId.'&poNo='.$ponumber.'&url=POSharedPage.do&action1=View&buyerOrgId=AEON_VN&formatXsl=http%3A%2F%2Feportal-localhost.b2b.com.my%2FeportalConf%2FxslTemplates%2F%2FFormatOrder_AEON_VN.xsl&userId=CONTY0000000369%23THUYLINH&supplierOrgId=CONTY0000000369&exportedFileFormatOption=null&defaultExportedFileFormat=CSV&exportedFileFormat=CSV&actionType=export',
            CURLOPT_HTTPHEADER => array(
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                'Accept-Language: en-US,en;q=0.9,vi-VN;q=0.8,vi;q=0.7,zh-TW;q=0.6,zh-CN;q=0.5,zh;q=0.4',
                'Cache-Control: max-age=0',
                'Connection: keep-alive',
                'Content-Type: application/x-www-form-urlencoded',
                'Cookie: cookieLanguage=vi_VN; cookieCoId=CONTY0000000369; cookieUserId=THUYLINH; cookieRememberMe=Y; '.$jsessionid,
                'DNT: 1',
                'Origin: https://aeonvn.b2b.com.my',
                'Referer: https://aeonvn.b2b.com.my/esupplier/pages/po/POSharedPage.do?poId='.$poId.'&buyerOrgId=AEON_VN&actionPage=View',
                'Sec-Fetch-Dest: document',
                'Sec-Fetch-Mode: navigate',
                'Sec-Fetch-Site: same-origin',
                'Sec-Fetch-User: ?1',
                'Upgrade-Insecure-Requests: 1',
                'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36',
                'sec-ch-ua: "Not_A Brand";v="99", "Google Chrome";v="109", "Chromium";v="109"',
                'sec-ch-ua-mobile: ?0',
                'sec-ch-ua-platform: "macOS"'
            ),
        ));

        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($response === false || $http_code !== 200) {
            return null;
        }

        $lines = explode("\n", $response);
        if (!isset($lines[1])) {
            return null;
        }
        $header = str_getcsv($lines[1]);
        if (empty($header)) {
            return null;
        }
        $masked_header = array();
        foreach ($header as $h){
            $masked_header[] = str_replace('"', '', $h);
        }

        for ($i = 2; $i < count($lines); $i++) {
            $line = $lines[$i];
            if (strpos($line, 'D') !== false && strpos($line, 'SD') == false) {
                $values = str_getcsv($line);
                if ($masked_header && $values) {
                    $row = array_combine($masked_header, $values);
                    if (isset($row['Ngay dat hang'])) {
                        return $row['Ngay dat hang'];
                    }
                }
                break;
            }
        }

        return null;
    }

    private function _aeon_is_within_last_7_days($order_date_raw)
    {
        if (empty($order_date_raw)) {
            return null;
        }
        $tz = new DateTimeZone('Asia/Ho_Chi_Minh');
        $dt = DateTime::createFromFormat('Ymd', $order_date_raw, $tz);
        if ($dt === false) {
            return null;
        }
        $now = new DateTime('now', $tz);
        $diff_days = (int)$now->diff($dt)->format('%r%a');
        if ($diff_days < -7) {
            return false;
        }
        return true;
    }


    /**
     *  POST /apis/search_haravan_order_by_name
     *  Payload: { "order_name": "#100245" }
     *
     *  Trả kết quả dạng JSON:
     *  {
     *      "success" : true|false,
     *      "message" : "...",
     *      "order"   : { ...object Haravan... } | null,
     *      "scanned" : 123        // số đơn đã duyệt
     *  }
     */
    public function search_haravan_order_by_name()
    {
        // 1. Lấy tham số
       $orderName = trim(
            $this->input->post('order_name')
        ?: $this->input->post('ordername')
        ?: $this->input->get('order_name')
        ?: $this->input->get('ordername')
        );
        if ($orderName === '') {
            return $this->output
                ->set_status_header(400)
                ->set_output(json_encode([
                    'success' => false,
                    'message' => 'Thiếu order_name'
                ]));
        }

        // 2. Gọi hàm dò 1 000 đơn gần nhất
        $result = $this->_search_haravan_order_by_order_name($orderName);

        // 3. Chuẩn bị dữ liệu trả về
        if ($result) {
            $payload = [
                'success' => true,
                'message' => 'Tìm thấy đơn hàng',
                'order'   => $result['order'],   // object Haravan (đã rút gọn)
                'scanned' => $result['scanned']
            ];
        } else {
            $payload = [
                'success' => false,
                'message' => 'Không tìm thấy đơn nào có name = ' . $orderName,
                'order'   => null,
                'scanned' => $result['scanned'] ?? 0
            ];
        }

        return $this->output
         ->set_status_header(200)
         ->set_content_type('application/json')
        ->set_output(json_encode($payload));
    }


   /**
     *  Dò tối đa 1 000 đơn Haravan mới nhất để tìm $orderName
     *
     *  @return array|false
     *          Nếu thấy   → ['order'=>$o, 'scanned'=>N]
     *          Không thấy → false (vẫn gán $this->last_scanned để controller lấy)
     */
    public function _search_haravan_order_by_order_name($orderName)
    {
        $needles = [$orderName];
        if ($orderName[0] !== '#') $needles[] = '#'.$orderName;

        $token      = $this->haravan_token;
        $shopDomain = 'moriitaliavn.myharavan.com';
        $headers    = [
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
            'Shop-Domain: ' . $shopDomain,
        ];

        $page    = 1;
        $limit   = 50;
        $scanned = 0;

        while ($scanned < 500) {
            $url = 'https://apis.haravan.com/com/orders.json'
                . '?limit='.$limit.'&page='.$page.'&order=created_at%20desc';

            $resp = $this->curl_json($url, $headers);
            if (!$resp['ok']) return false;

            $json = json_decode($resp['body']);
            if (empty($json->orders)) break;

            foreach ($json->orders as $o) {
                $scanned++;
                if (!empty($o->name) && in_array($o->name, $needles, true)) {
                    return ['order'=>$o, 'scanned'=>$scanned];
                }
                if ($scanned >= 500) break;
            }

            if (count($json->orders) < $limit) break;
            $page++;
        }
        return false;
    }

    public function download_external_file()
    {
        // 1. Lấy URL
        $url = trim($this->input->get('url', true));
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['error'=>'Thiếu hoặc sai định dạng URL']));
        }

        // 2. Chuẩn bị thư mục
        $targetDir = FCPATH . 'temp/files/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // 3. Tạo tên file an toàn
        $origName = basename(parse_url($url, PHP_URL_PATH));
        $safeName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $origName);
        $filename = time() . '_' . $safeName;
        $filePath = $targetDir . $filename;

        // 4. Mở file handle để ghi
        if (($fp = fopen($filePath, 'wb')) === false) {
            return $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['error'=>'Không thể tạo file trên server']));
        }

        // 5. Khởi tạo cURL streaming
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FILE           => $fp,            // ghi thẳng vào file
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CONNECTTIMEOUT => 30,             // timeout kết nối 30s
            CURLOPT_TIMEOUT        => 120,            // timeout tổng 120s
            CURLOPT_BUFFERSIZE     => 1024 * 1024,    // buffer 1MB
        ]);

        $success   = curl_exec($ch);
        $curlErr   = curl_error($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        // 6. Xử lý khi download lỗi
        if (!$success || $httpCode !== 200) {
            // xoá file rác nếu có
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            log_message('error', "Download failed: {$curlErr}, HTTP code {$httpCode}, URL {$url}");
            return $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'error'      => 'Không thể tải file',
                    'http_code'  => $httpCode,
                    'curl_error' => $curlErr,
                ]));
        }

        // 7. Trả về link public
        $fileUrl = base_url("temp/files/{$filename}");
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['file_url'=>$fileUrl]));
    }   



    // ─────────────────────────────────────────────────────────────────
    // LOTTE CRAWL API METHODS
    // ─────────────────────────────────────────────────────────────────

    /**
     * POST /admin/external_products/lotte_crawl
     * Trigger a full LOTTE order crawl with provided cookie+csrf+dates.
     * Called by Chrome extension after extracting session info.
     */

    // ─────────────────────────────────────────────────────────────────
    // LOTTE CRAWL API METHODS (via chrome extension)
    // ─────────────────────────────────────────────────────────────────

    /** POST /apis/lotte_crawl — Trigger order crawl from extension */
    public function lotte_crawl()
    {
        header('Content-Type: application/json');

        $cookie   = $this->input->post('cookie');
        $csrf_tag = $this->input->post('csrf_tag');
        $from     = $this->input->post('from') ?: date('d/m/Y', strtotime('-30 days'));
        $to       = $this->input->post('to')   ?: date('d/m/Y');
        $source   = $this->input->post('source') ?: 'extension';

        if (empty($cookie) || empty($csrf_tag)) {
            echo json_encode(['success' => false, 'message' => 'cookie va csrf_tag bat buoc']);
            return;
        }

        // Tạo log entry
        $this->db->insert('lotte_crawl_log', [
            'triggered_at'   => date('Y-m-d H:i:s'),
            'trigger_source' => $source,
            'from_date'      => $from,
            'to_date'        => $to,
            'status'         => 'running',
        ]);
        $log_id = $this->db->insert_id();

        // 1. Lấy danh sách đơn từ LOTTE
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => 'https://edilottemart.vn/app/po/splyord_selSearchList',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => '_search=false&nd='.time().'000&rows=100&page=1&sidx=ORD_PROC_NM&sord=asc'
                                    . '&_csrf='.$csrf_tag.'&venCd=&ordSlipNo=&ordFrDy='.$from.'&ordToDy='.$to
                                    . '&strCd=&ordProcCd=&splyFrDy=&splyToDy=',
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
                'Cookie: '.$cookie,
                'X-CSRF-TOKEN: '.$csrf_tag,
                'X-Requested-With: XMLHttpRequest',
                'Referer: https://edilottemart.vn/app/po/splyord?_code=10084',
                'User-Agent: Mozilla/5.0 (compatible)',
            ],
        ]);
        $list_response = curl_exec($curl);
        curl_close($curl);

        $list_data    = json_decode($list_response, true);
        $orders       = $list_data['list'] ?? $list_data['rows'] ?? [];
        $orders_found = count($orders);
        $orders_saved = 0;
        $errors       = [];

        // 2. Lấy chi tiết từng đơn và lưu vào DB
        foreach ($orders as $order) {
            $ordSlipNo = $order['ordSlipNo'] ?? ($order['id'] ?? null);
            if (!$ordSlipNo) continue;

            $uniquekey = 'LOTTE'.$ordSlipNo;
            $existing  = $this->db->where('uniquekey', $uniquekey)->get('external_data_mapping')->row();

            if (!$existing) {
                $curl2 = curl_init();
                curl_setopt_array($curl2, [
                    CURLOPT_URL            => 'https://edilottemart.vn/app/po/splyord_selSearchSubList',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 15,
                    CURLOPT_CUSTOMREQUEST  => 'POST',
                    CURLOPT_POSTFIELDS     => json_encode(['ordSlipNo' => $ordSlipNo]),
                    CURLOPT_HTTPHEADER     => [
                        'Content-Type: application/json; charset=UTF-8',
                        'Cookie: '.$cookie,
                        'X-CSRF-TOKEN: '.$csrf_tag,
                        'X-Requested-With: XMLHttpRequest',
                        'Referer: https://edilottemart.vn/app/po/splyord?_code=10084',
                        'User-Agent: Mozilla/5.0 (compatible)',
                    ],
                ]);
                $detail = curl_exec($curl2);
                curl_close($curl2);

                if ($detail && json_decode($detail)) {
                    $ok = $this->db->insert('external_data_mapping', [
                        'uniquekey'  => $uniquekey,
                        'rel'        => 'Order',
                        'root_id'    => $ordSlipNo,
                        'target_id'  => '',
                        'title'      => 'Order '.$ordSlipNo,
                        'dateadded'  => date('Y-m-d H:i:s'),
                    ]);
                    if ($ok) {
                        $orders_saved++;
                    } else {
                        $errors[] = "Luu that bai: $ordSlipNo";
                    }
                } else {
                    $errors[] = "Khong lay duoc chi tiet: $ordSlipNo";
                }
                usleep(150000);
            } else {
                $orders_saved++;
            }
        }

        // Cập nhật log
        $this->db->where('id', $log_id)->update('lotte_crawl_log', [
            'orders_found' => $orders_found,
            'orders_saved' => $orders_saved,
            'errors'       => empty($errors) ? null : implode('; ', array_slice($errors, 0, 5)),
            'status'       => empty($errors) ? 'success' : 'error',
        ]);

        echo json_encode([
            'success'      => true,
            'log_id'       => $log_id,
            'orders_found' => $orders_found,
            'orders_saved' => $orders_saved,
            'errors'       => array_slice($errors, 0, 5),
            'from'         => $from,
            'to'           => $to,
        ]);
    }

    /** GET /apis/lotte_status — Returns crawl stats for Chrome extension */


    public function lotte_status()
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');

        $last  = $this->db->query("SELECT * FROM tbllotte_crawl_log ORDER BY id DESC LIMIT 1")->row_array();
        $total = $this->db->query("SELECT COUNT(*) as cnt FROM tblexternal_data_mapping WHERE uniquekey LIKE 'LOTTE%' AND rel='Order'")->row_array();

        echo json_encode([
            'success'      => true,
            'total_orders' => (int)($total['cnt'] ?? 0),
            'last_crawl'   => $last ?: null,
        ]);
    }

    public function lotte_logs()
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');

        $exists = $this->db->table_exists('lotte_crawl_log');
        $logs   = $exists ? $this->db->order_by('id', 'DESC')->limit(20)->get('lotte_crawl_log')->result_array() : [];
        echo json_encode(['success' => true, 'logs' => $logs]);
    }

}
