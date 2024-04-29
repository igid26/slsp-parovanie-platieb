<?php



function manualne_napojenie_na_slsp () {



header('Content-Type: text/html; charset=utf-8');




$hostname = '{imap.websupport.sk:993/imap/ssl}INBOX';
$username = 'objednavky@misijnediela.sk'; //change this
$password = 'Lazaretska3232,';

$inbox = imap_open($hostname,$username,$password) or die('Cannot connect to Gmail: ' . imap_last_error());




$nums=imap_num_msg($inbox);
for ($i=1;$i<=$nums;$i++){
    $overview = imap_fetch_overview($inbox, $i, 0);
    if(strtolower($overview[0]->fromaddress) == 'vypis@slsp.sk') {
    echo imap_utf8($overview[0]->subject) . imap_utf8($overview[0]->date) . '<br>';
    }

    
    }
imap_close($inboxi);








//Connection Details
$hostname = '{imap.websupport.sk:993/imap/ssl}INBOX';
$username = 'objednavky@misijnediela.sk'; //change this
$password = 'Lazaretska3232,'; //change this

//Search parameters
//See http://uk3.php.net/manual/en/function.imap-search.php for possible keys
//SINCE date should be in j F Y format, e.g. 9 August 2013
$searchArray = array('SUBJECT'=>'Výpis Papezske misijne diela - FARNOSŤ');

//Save attachement file to 
$saveToPath = plugin_dir_path( __FILE__ ) . '/dump/'; //change this
//Extract zip files to
$unzipDest = plugin_dir_path( __FILE__ ) .'/files/'; //change this

require "funkcia-emailu.php";




$xa = new exAttach($hostname,$username,$password);
$xa->get_files($searchArray, $saveToPath);
$xa->extract_zip_to($unzipDest);





$dir = plugin_dir_path( __FILE__ ) .'/files/';
if (is_dir($dir)) {
  if ($dh = opendir($dir)) {
    while (($file = readdir($dh)) !== false) {
      if (($file !== '.') && ($file !== '..') ) {
    $fille_array[] = $file;
    }
  }
  closedir($dh);
  }
}
//print_r($fille_array);
$nazov_suboru = max($fille_array);

$nazov_suboru = 'V000042.xml';



$xml = simplexml_load_file($unzipDest . $nazov_suboru);






$zaklad = $xml->BkToCstmrStmt->Stmt->Ntry;





print_r($zaklad);


$rozdelenie = '';
$num = '';
$vyriabilne_simboly = array();
foreach ($zaklad as $zaklads) {




$cel_fraza = $zaklads->NtryDtls->TxDtls->Refs->EndToEndId;





if($cel_fraza != 'NOTPROVIDED') {
$str_arr = explode ("/", $cel_fraza);
if(!empty($str_arr[1])) {
$rozdelenie = preg_match('/\d+/', $str_arr[1], $numMatch);
}

if(!empty($numMatch[0])) {
$num = $numMatch[0];
}

}
$vyriabilne_simboly[] = $num;
}





array_push($vyriabilne_simboly,"88853061", "88853058"); 
//array_push($vyriabilne_simboly_nove,"77753078");


$vsetky_objednavky = array();
$loop = new WP_Query( array(
    'post_type'         => 'shop_order',
    'post_status'       =>  'wc-on-hold',
    'orderby'=> 'post_date', 
    'order' => 'ASC',
    'posts_per_page'    => 10,

) ); 
              ?>

              <?php if ($loop->have_posts()): while ($loop->have_posts()) : $loop->the_post(); 

                $order_id = $loop->post->ID; 
                
                $order = wc_get_order( $order_id );
                $items = $order->get_items();
                foreach ( $items as $item ) {
                $product_id = $item->get_product_id();
                $term_list = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
                }
foreach($term_list as $term_lists) {
$cislo_kategorie = $term_lists;
} 



if (($cislo_kategorie === 169) OR ($cislo_kategorie === 170)) {  
$vsetky_objednavky[] = '777' . $order_id;
}

if (($cislo_kategorie === 168)) {
$vsetky_objednavky[] = '888' . $order_id;
}

if (($cislo_kategorie === 199) OR ($cislo_kategorie === 201)) {
$vsetky_objednavky[] = '444' . $order_id;
}

                
                   

                
endwhile; ?>
<?php wp_reset_postdata(); else: echo '<p>'.__('Sorry, no posts matched your criteria.').'</p>'; 
endif; ?>   

<?php $result = array_intersect($vsetky_objednavky,$vyriabilne_simboly);   

//print_r($vyriabilne_simboly);



//print_r($vsetky_objednavky);

print_r($result);

foreach($result as $results) {
echo $results . '<br><br>';  
$nove_idcko = substr($results, 3);
$order = new WC_Order($nove_idcko);
$order->update_status('completed', 'order_note');
}



$to = 'majan261166@gmail.com';
$subject = 'Import objednávok prebehol v poriadku ' . $nazov_suboru;
$body = 'The email body content';
$headers = array('Content-Type: text/html; charset=UTF-8');

//wp_mail( $to, $subject, $body, $headers );
           

} 









?>