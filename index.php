<?php
/*
Plugin Name: Napojenie na SLSP
Plugin URI:  http://link to your plugin homepage
Description: Plugin sťahuje z emailu výpisy z účtu. Následne rozbaľuje .zip súbor a parsuje jeho obsah. Výsledné dáta porovnáva s objednávkami na základe VS symbolu. Objednávkam sa následne mení ich stav.
Version:     1.0
Author:      Igor Majan
Author URI:  http://link to your website
License:     GPL2 etc
License URI: http://link to your plugin license
*/  

function napojenie_na_slsp () {

header('Content-Type: text/html; charset=utf-8');

//Detaily pripojenia
$hostname = '{imap.websupport.sk:993/imap/ssl}INBOX'; 
$username = '.....'; //prihlasovacie meno emailu kam chodia výpisy
$password = '.....'; //heslo k emailu kam chodia výpisy

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
//print_r($fille_array);
$nazov_suboru = max($fille_array);

$xml = simplexml_load_file($unzipDest . $nazov_suboru);



//echo (string)$xml->GrpHdr->MsgRcpt->PstlAdr['PstCd'] . '<br />';



$zaklad = $xml->BkToCstmrStmt->Stmt->Ntry;

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


if (($cislo_kategorie === 188) OR ($cislo_kategorie === 224) OR ($cislo_kategorie === 324) OR ($cislo_kategorie === 235) ) {
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
           






           

} 



include ('manualne.php');

include ('manualne2.php');



function filter_woocommerce_order_number( $order_number, $order ) {
    // Loop through order items
    foreach ( $order->get_items() as $item ) {
        // Product ID
        $product_id = $item->get_variation_id() > 0 ? $item->get_variation_id() : $item->get_product_id();

        // Has term (product category)
        if ( has_term( array( 'omsove-milodary', 'pod-kategoria' ), 'product_cat', $product_id ) ) {
            return '777' . $order_number;
        } elseif ( has_term( array( 'misijne-predmety' ), 'product_cat', $product_id ) ) {
            return '888' . $order_number;
        } elseif ( has_term( array( 'sladka-pomoc-2022' ), 'product_cat', $product_id ) ) {
            return '444' . $order_number;
        } elseif ( has_term( array( 'omsa-ako-dar-2022', 'podkategoria-pohladnice-2022' ), 'product_cat', $product_id ) ) {
            return '500' . $order_number;
        } elseif ( has_term( array( 'omsa-ako-dar-2023', 'podkategoria-pohladnice-2023' ), 'product_cat', $product_id ) ) {
            return '500' . $order_number;
        } else  {
            return $order_number;
        }
    }
    
    return $order_number;
}
add_filter( 'woocommerce_order_number', 'filter_woocommerce_order_number', 10, 2 );






if( !wp_next_scheduled('ovrerenie_platby_slsp' ) ) 
   wp_schedule_event( strtotime('00:10:00'), 'daily', 'ovrerenie_platby_slsp' );
 
add_action( 'ovrerenie_platby_slsp', 'napojenie_na_slsp', 10, 3 );


if( !wp_next_scheduled('ovrerenie_platby_slsp2' ) ) 
   wp_schedule_event( strtotime('00:30:00'), 'daily', 'ovrerenie_platby_slsp2' );
 
add_action( 'ovrerenie_platby_slsp2', 'napojenie_na_slsp', 10, 3 );


if( !wp_next_scheduled('ovrerenie_platby_slsp3' ) ) 
   wp_schedule_event( strtotime('00:50:00'), 'daily', 'ovrerenie_platby_slsp3' );
 
add_action( 'ovrerenie_platby_slsp3', 'napojenie_na_slsp', 10, 3 );









?>
