<?php
  /**
   *  Plugin Name: Radio Online
   *  Version: 1.0
   *  Plugin URI: http://www.radiourionline.ro/plugin-cu-widget-radiouri-online-s195.html 
   *  Description: Posturi de radio online din toate categoriile prezente pe http://www.radiourionline.ro
   *  Author: GeorgeJipa
   *  Author URI: http://www.radiourionline.ro 
   **/

  /**
   *  Include feed.php, necesar functiei fetch_feed();
   **/     
  include_once(ABSPATH . WPINC . '/feed.php');   
  
  /**
   *  Cateva variabile necesare functionarii plugin-ului
   **/
   $adresa_site = 'http://www.radiourionline.ro/';
   $api_url = $adresa_site . 'api/index.php'; 
   $adresa = get_settings('siteurl');
   $plugin_path = $adresa.'/wp-content/plugins/radiouri-online';
         
  /**
   *  getCateg(): preia din API toate categoriile de radio-uri si le confrunta cu categoriile bifate anterior (daca au fost bifate) 
   **/
   function getCateg(){
    global $api_url;
    
    $data = array('method' => 'getCateg', 'time' => time());
    $query = http_build_query($data);
    
    $rss = fetch_feed($api_url.'?'.$query); 
    $items = $rss->get_items();
    
    foreach($items as $item){
      $categ = $item->data['data'];
      
      if(get_option('rw_categ_bifate') == TRUE){
        $categBifate = unserialize(get_option('rw_categ_bifate'));
        $checked = (in_array($categ, $categBifate)) ? 'checked' : '';
      } else {
        $checked = '';
      }
      echo '<input type="checkbox" name="categorii[]" value="'.$categ.'" '.$checked.'/> '.$categ.' ';
    }
   }

  /**
   *  getCateg(): preia din API toate categoriile de radio-uri si le confrunta cu categoriile bifate anterior (daca au fost bifate) 
   **/
   function getRCateg(){
    global $api_url;
    
    $categBifate = unserialize(get_option('rw_categ_bifate')); 
    $data = array('method' => 'getRCateg', 'cats' => $categBifate, 'time' => time());
    $query = http_build_query($data);
    
    $rss = fetch_feed($api_url.'?'.$query);
    $items = $rss->get_items();
    
    foreach($items as $item){
      $post = $item->data['data'];
      $idpost = $item->data['attribs']['']['idpost'];
      $stream = $item->data['attribs']['']['stream'];

      if(get_option('rw_post_bifate') == TRUE) {
        $postBifate = unserialize(get_option('rw_post_bifate'));
        $checked = (in_array($idpost, $postBifate)) ? 'checked' : '';
      } else  {
        $checked = '';
      }
      echo '<input type="checkbox" name="posturi[]" value="'.$idpost.'" '.$checked.'/> <a href="'.$stream.'" target="_blank">'.$post.'</a> ';
    }
   }
  
  /**
   *  getRadios(): preia informatii din API despre toate posturile radio bifate in ADMIn
   **/
   function getRadios(){ // este setat automat la maxim 5 radio-uri per categorie
    global $api_url, $plugin_path, $adresa_site;
    
    // Afisam widget doar daca exista posturi radio bifate
    if(get_option('rw_post_bifate') == TRUE){
      $max = get_option('rw_widget_max');
            
      $postBifate = unserialize(get_option('rw_post_bifate'));
      $data = array('method' => 'getRadios', 'ids' => $postBifate);
      $query = http_build_query($data);
      $rss = fetch_feed($api_url.'?'.$query);
      $items = $rss->get_items();

      echo '<ul>';
      foreach($items as $item){
        $urlcat = $item->data['attribs']['']['urlcat'];
        $numecat = $item->data['attribs']['']['numecat'];
      
        echo '<li><strong style="font-size:14px;">'.$numecat.'</strong>';
        $i=1;
		echo '<ul>';
        foreach($item->data['child']['']['post'] as $post){
          $stream = $post['attribs']['']['stream'];
		  $postid = $post['attribs']['']['idpost'];
          $post = $post['data']; 
          
          if($i<=$max) echo '<li><a target="_blank" rel="nofollow" href="'.$adresa_site . 'listen.php?id=' . $postid.'"  title="Asculta '.$post.'">'.$post.' <img src="'.$plugin_path.'/images/asculta.png" border="0" /></a></li>';
          $i++;
        }
        echo '</ul></li>';
      }
      echo '<a target="_blank" href="'.$adresa_site.'" title="asculta toate posturile de radio online" rel="nofollow"><img src="'.$plugin_path.'/images/radio-online.gif" style="border:none;" alt="radio online: muzica populara, dance, house, radio manele..." /></a>';
      echo '</ul>'; 
    } else {
      echo '<ul><li>Niciun post selectat!</li></ul>';
    }
   }
   
  /**
   *  rw_widget(): inregistrare widget
   **/     
  function register_rw_widget($args) {
    extract($args);
    
    echo $before_widget;
    $title = get_option('rw_widget_title');
    echo $args['before_title'].' '.$title.' '.$args['after_title'];
    getRadios(); 
    echo $after_widget;
  }
  	  
  function register_rw_control(){
    $max = get_option('rw_widget_max');
    $title = get_option('rw_widget_title');
    
    echo '<p><label>Titlu RadioWidget: <input name="title" type="text" value="'.$title.'" /></label></p>';
    echo '<p><label>Posturi / categorie: <input name="max" type="text" value="'.$max.'" /></label></p>';
      
    if(isset($_POST['max'])){
      update_option('rw_widget_max', attribute_escape($_POST['max']));
      update_option('rw_widget_title', attribute_escape($_POST['title']));
    }
  }    
  
  function rw_widget() {
  	 register_widget_control('RadioWidget', 'register_rw_control'); 
  	 register_sidebar_widget('RadioWidget', 'register_rw_widget');
  }          
   
  /**
   *  rw_admin(): partea de administrare a plugin-ului
   **/     
   function rw_admin(){
    echo '<div class="wrap">';
    echo '<h2>Setari RadioWidget</h2>';
    if(isset($_POST['scategorii']) && isset($_POST['categorii'])){ // in cazul in care una dintre ele nu este exista, nu se face submit
        $categorii = serialize($_POST['categorii']);
        if(get_option('rw_categ_bifate') === FALSE){
          add_option('rw_categ_bifate', $categorii);
        } else {
          delete_option('rw_categ_bifate');
          add_option('rw_categ_bifate', $categorii);
        }
    }
    echo '<div class="widefat" style="padding: 5px">1) Alege una sau mai multe categorii:<br /><br />';
    echo '<form method="post" name="categorii" target="_self">';
    getCateg();
    echo '<input name="scategorii" type="hidden" value="yes" />';
    echo '<br /><br /><input type="submit" name="Submit" value="Listeaza posturi &raquo;" />';    
    echo '</form>';
    echo '</div>';
    echo '<br />';
    if(isset($_POST['scategorii']) && isset($_POST['categorii'])){
      echo '<div class="widefat fade" style="padding: 5px">2) Alege posturile radio pe care vrei sa le afisezi <br /><br />';
      echo '<form method="post" name="posturi" target="_self">';      
      getRCateg();
      echo '<input name="sposturi" type="hidden" value="yes" />';
      echo '<br /><br /><input type="submit" name="Submitt" value="Salveaza posturi &raquo;" />';    
      echo '</form>';
      echo '</div>';   
    }
    if(isset($_POST['sposturi']) && isset($_POST['posturi'])){
        $posturi = serialize($_POST['posturi']);
        if(get_option('rw_post_bifate') === FALSE){
          add_option('rw_post_bifate', $posturi);
        } else {
          delete_option('rw_post_bifate');
          add_option('rw_post_bifate', $posturi);
        }
        echo '<div id="message" class="updated fade"><p><strong>Posturile radio au fost salvate!</strong></p></div>';        
      }    
    echo '</div>';
   }   


  /**
   *  rw_addpage(): adauga pagina de administrare in meniul Wordpress
   **/     
  function rw_addpage() {
    add_submenu_page('options-general.php', 'Radio Widget', 'Radio Widget', 10, __FILE__, 'rw_admin');
  }
  
  /**
   *  Actions & Options
   **/     
  add_action('admin_menu', 'rw_addpage');
  add_action("plugins_loaded", "rw_widget");
  add_option('rw_widget_max', '5');
  add_option('rw_widget_title', 'Radiouri Online');  
?>
