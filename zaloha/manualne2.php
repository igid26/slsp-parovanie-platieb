<?php



function manualne_napojenie_na_slsp_nove () {








header('Content-Type: text/html; charset=utf-8');

//Connection Details
$hostname = '{imap.websupport.sk:993/imap/ssl}INBOX';
$username = 'objednavky@misijnediela.sk'; //change this
$password = 'Lazaretska3232,'; //change this

//Search parameters
//See http://uk3.php.net/manual/en/function.imap-search.php for possible keys
//SINCE date should be in j F Y format, e.g. 9 August 2013
$searchArray = array('SUBJECT'=>'Výpis Papezske misijne diela - FARNOSŤ - ', 'SINCE'=>date('j F Y',strtotime('4 month ago')));

//Save attachement file to 
$saveToPath = plugin_dir_path( __FILE__ ) . '/dump/'; //change this
//Extract zip files to
$unzipDest = plugin_dir_path( __FILE__ ) .'/files/'; //change this


require "funkcia-emailu.php";

//Create an object
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
print_r($fille_array);
$nazov_suboru = max($fille_array);

$nazov_suboru = 'V000072.xml';



$xml = simplexml_load_file($unzipDest . $nazov_suboru);



//echo (string)$xml->GrpHdr->MsgRcpt->PstlAdr['PstCd'] . '<br />';

$zaklad = $xml->BkToCstmrStmt->Stmt->Ntry;


$zaklad = $xml->BkToCstmrStmt->Stmt->Ntry;


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

echo $num . '<br>';

}
$vyriabilne_simboly[] = $num;
}

//print_r($vyriabilne_simboly);

array_push($vyriabilne_simboly,"88853061", "88853058"); 
//array_push($vyriabilne_simboly_nove,"77753078");


$vsetky_objednavky = array();
$loop = new WP_Query( array(
    'post_type'         => 'shop_order',
    'post_status'       =>  'wc-on-hold',
    'posts_per_page'    => 10000,
    'orderby' => 'date', 
    'order' => 'DESC'

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


if (($cislo_kategorie === 188) OR ($cislo_kategorie === 224)) {
$vsetky_objednavky[] = '500' . $order_id;
}
                
                   

                
endwhile; ?>
<?php wp_reset_postdata(); else: echo '<p>'.__('Sorry, no posts matched your criteria.').'</p>'; 
endif; ?>   

<?php $result = array_intersect($vsetky_objednavky,$vyriabilne_simboly);   

//print_r($vyriabilne_simboly);



print_r($vsetky_objednavky);

print_r($result);

foreach($result as $results) {
echo $results . '<br><br>';  
$nove_idcko = substr($results, 3);
$order = new WC_Order($nove_idcko);
$order->update_status('completed', 'order_note');
}
           





$datum = date("l jS \of F Y h:i:s A");

$to = 'majan261166@gmail.com';
$subject = 'Import objednávok prebehol v poriadku ' . $nazov_suboru;
$body = implode("\n", $vyriabilne_simboly);
$headers = array('Content-Type: text/html; charset=UTF-8');

wp_mail( $to, $subject, $body, $headers );



           

} 









?>