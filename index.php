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
$hostname = '{imap.websupport.sk:993/imap/ssl}INBOX'; // pridaj adresu servera prichádzajúcej pošty v rátane pričinka (napr. "{imap.websupport.sk:993/imap/ssl}INBOX")
$username = '.....'; //prihlasovacie meno emailu kam chodia výpisy
$password = '.....'; //heslo k emailu kam chodia výpisy

//Search parameters
//See http://uk3.php.net/manual/en/function.imap-search.php for possible keys
//SINCE date should be in j F Y format, e.g. 9 August 2013
$searchArray = array('SUBJECT'=>'Výpis ..... - ', 'SINCE'=>date('j F Y',strtotime('4 month ago')));  //Sem zadajte text predmetu emailu, respektíve výpisu. Väčšinou je to v tvare Výpis ..... Pomocou tohto identifikujete emaily, ktoré má spracúvať

//Save attachement file to 
$saveToPath = plugin_dir_path( __FILE__ ) . '/dump/'; //Sem bude ukladať zip
//Extract zip files to
$unzipDest = plugin_dir_path( __FILE__ ) .'/files/'; //sem bude ukladať rozbalené súbory


require "funkcia-emailu.php"; //načítanie funkcie na spracovanie emailov

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
$nazov_suboru = max($fille_array); //spracuje rozbalený a sprovaný súbor s najväčším číslom v názve

$xml = simplexml_load_file($unzipDest . $nazov_suboru);




$zaklad = $xml->BkToCstmrStmt->Stmt->Ntry;

$xml = simplexml_load_file($unzipDest . $nazov_suboru);



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

//print_r($vyriabilne_simboly); //vytlaci všetky variabilné symboly

array_push($vyriabilne_simboly,"88853061", "88853058"); 


//Teraz potrebujeme vypísať posledných 1000 objednávok a následne ich budeme porovnávať podľa ID objednávky. 
$vsetky_objednavky = array();
$loop = new WP_Query( array(
    'post_type'         => 'shop_order',
    'post_status'       =>  'wc-on-hold',
    'posts_per_page'    => 1000,
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


//podmienka, že chcem párovať len objednávky z danej kategórie (podla term id)
if (($cislo_kategorie === 169) OR ($cislo_kategorie === 170)) {  
$vsetky_objednavky[] =  $order_id;
} 

               
endwhile; ?>
<?php wp_reset_postdata(); else: echo '<p>'.__('Sorry, no posts matched your criteria.').'</p>'; 
endif; ?>   

<?php $result = array_intersect($vsetky_objednavky,$vyriabilne_simboly);   //porovnanie hodnôt id objednávok a VS symbolov

//print_r($vyriabilne_simboly);



foreach($result as $results) {
echo $results . '<br><br>';  //vypísanie objednávok, ktoré boli nájdené
$nove_idcko = substr($results, 3);
$order = new WC_Order($nove_idcko);
$order->update_status('completed', 'order_note');  //aktualizovanie statusu objednávky na vybavené
}
                  

} 



//include ('manualne.php'); funkcia v časti manualne.php slúži na manuálne zadávanie rozbaleného súboru. V prípade, že sa zip nerozbalí spravne




//vytvorenie cronjobu - raz za deň o pol 1 ráno, kedy nie je zaťažený server.

if( !wp_next_scheduled('ovrerenie_platby_slsp' ) ) 
   wp_schedule_event( strtotime('00:30:00'), 'daily', 'ovrerenie_platby_slsp' );
 
add_action( 'ovrerenie_platby_slsp', 'napojenie_na_slsp', 10, 3 );









?>
