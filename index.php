<?php 
/*
Plugin Name: Emag Profitshare
Plugin URI: http://www.iamntz.com/category/goodies/wordpress-profitshare/
Description: Înlocuiește link-urile emag cu linkul de profitshare.
Author: Ionut Staicu
Version: 2.0.9
Author URI: http://www.iamntz.com
*/

class NtzReferral{
  protected 
    $default_profitshare,
    $default_url_base = 'go',
    $user_settings,
    $emag_regex = "/http:\/\/(www\.)?emag\.ro\//i",
    $emag_full_url = "~(http:\/\/(www\.)?emag\.ro\/)([a-zA-Z0-9\/\-\–\_\=\.\?\&\;\/\#\~\+]*)~",
    $emag_profitshare_regex = "~(http:\/\/profitshare.emag\.ro\/)([a-zA-Z0-9\/\-\–\_\=\.\?\&\;\/\#\~\+]*)~",
    $plugin_version = 2,
    $wpdb,
    $db_name;

  function __construct(){
    global $wpdb;
    $this->user_settings = (array) get_option( 'ntz_referral_settings' );
    $this->wpdb = $wpdb;
    $this->db_name = $this->wpdb->prefix.'ntz_referral';

    $old_profithsare = get_option('profitshare_user'); // upgrade de la versiunea 1

    $this->old_profithsare = !empty( $old_profithsare ) ? $old_profithsare : null;
    $this->default_profitshare = "d4df812647a68d27a5cc35e37c2fbf2f";

    
    if( isset( $_GET['ntz_do'] ) ){
      switch ( $_GET['ntz_do'] ) {
        case 'redirect':
          $this->redirect();
        break;
        case 'quick_profit':
          $this->quick_profit();
        break;
      }
    }
    
    

    if( is_admin() ){
      // daca suntem pe pagina de admin verificam daca pluginul este instalat
      // si afisam meniul. altfel, inlocuim links
      if( (int) get_option( 'ntz_referral_version' ) !== $this->plugin_version ){ $this->maybe_install(); }
      add_action( 'admin_init', array( &$this, 'admin_init' ) );
      add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
    }else {
      add_action( 'the_content', array( &$this, 'replace_frontend_links' ), 1 );
    }

    if( $this->user_settings['quick_generator'] == 1 ){ // afisam quick generatorul
      if( is_user_logged_in() && current_user_can( 'edit_posts' ) ){ // folosim jquery pentru a genera link-ul rapid
        wp_enqueue_script('jquery');
      
        add_action( 'admin_footer', array( &$this, 'quick_generator_menu'), 999 );
        add_action( 'wp_footer', array( &$this, 'quick_generator_menu'), 999 );
      }
    }
  } // __construct

/* ============================= */
/* = functii folosite in admin = */
/* ============================= */

  public function maybe_install(){
    if(@is_file(ABSPATH.'/wp-admin/upgrade-functions.php')) {
      include_once(ABSPATH.'/wp-admin/upgrade-functions.php');
    } elseif(@is_file(ABSPATH.'/wp-admin/includes/upgrade.php')) {
      include_once(ABSPATH.'/wp-admin/includes/upgrade.php');
    }
    $charset_collate = '';
    if($wpdb->supports_collation()) {
      if(!empty($wpdb->charset)) {
        $charset_collate = "DEFAULT CHARACTER SET {$this->wpdb->charset}";
      }
      if(!empty($wpdb->collate)) {
        $charset_collate .= " COLLATE {$this->wpdb->collate}";
      }
    }

    $db_schema = "CREATE TABLE {$this->db_name} (
      `id` BIGINT(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
      `post_id` BIGINT(20) NOT NULL,
      `url` VARCHAR(255) NOT NULL,
      `short_url` VARCHAR(255) NOT NULL,
      `hits` BIGINT(20) NOT NULL,
      INDEX ( `post_id` , `url` )
    ) $charset_collate;";
    maybe_create_table($this->db_name, $db_schema);
    unset( $db_schema );
    update_option( 'ntz_referral_version', $this->plugin_version );
  } // maybe_install

  public function admin_menu(){
    add_options_page( 'Setări Profitshare', 'Setări Profitshare', 'manage_options', __FILE__, array( &$this, 'admin_page' ) );
  } // admin_menu

  public function admin_init(){
    $custom_rewrite = plugin_basename( __FILE__ ) . '?ntz_do=redirect&url_id=$1&url_name=$2';
    if( $this->user_settings['short_links'] ){
      add_rewrite_rule( ( !empty( $this->user_settings['short_url_base'] ) ? $this->user_settings['short_url_base'] : $this->default_url_base ) .'/([0-9]*)/?([a-zA-Z0-9\-]*)', $custom_rewrite, 'top' );
    }else {
      flush_rewrite_rules();
    }
    register_setting( 'ntz_referral_options', 'ntz_referral_settings', array( &$this, 'save_settings' ) );
  } // admin_init

  public function admin_page(){ ?>
  <style type="text/css" media="screen">
    .ntz_referral input[type="text"] {
      width:300px;
    }
  </style>
  <div class="wrap">
    <h2>Setări Profitshare</h2>
    <form method="post" action="options.php">

      <?php 
        settings_fields('ntz_referral_options');
        $options = get_option('ntz_referral_settings');

        $isDefaultKey = false;
        if( empty( $this->user_settings['profitshare_key'] ) || $this->user_settings['profitshare_key'] == $this->default_profitshare ){
          $isDefaultKey = true;
        }
      ?>

      <table class="form-table ntz_referral">

        <tr valign="top">
          <th scope="row">
            <label for="ntz_referral_settings[profitshare_key]">Profitshare Key</label><br/>
            <small>Adaugă orice cod generat de profitshare (<a href="http://img.iamntz.com/jing/2012-11-13_1132_001.png" target="_blank">?</a>)</small>
          </th>
          <td>
            <input type="text"
              name="ntz_referral_settings[profitshare_key]"
              id="ntz_referral_settings[profitshare_key]" <?php if( $isDefaultKey ) { echo ' class="profitshareError"' ; } ?>
              value="<?php echo !empty( $options['profitshare_key'] ) ? $options['profitshare_key'] : $this->default_profitshare; ?>" />
            <?php if( $isDefaultKey ) { ?>
              <style type="text/css" media="screen">
                input.profitshareError {
                  -moz-box-shadow:0px 0px 5px rgba(255, 0, 0, 1) ;
                  -webkit-box-shadow:0px 0px 5px rgba(255, 0, 0, 1) ;
                  box-shadow:0px 0px 5px rgba(255, 0, 0, 1) ;
                  border:1px solid #c00 !important;
                }
                div.profitshareError {
                  border:1px solid #c00;
                  padding:10px;
                  width:300px;
                  margin-top:10px;
                  -moz-border-radius:5px;
                  border-radius:5px;
                  background:#fff;
                }
                .profitshareError p,
                .profitshareError h4 {margin:0;}
              </style>
              <div class="profitshareError">
                <h4>Atenție!</h4>
                <p>Codul profitshare nu este setat!</p>
              </div>
            <?php } ?>
          </td>
        </tr>

        <tr valign="top">
          <th scope="row" style="width:240px">
          <label for="ntz_referral_settings[short_links]">Scurtează link-urile</label></th>
          <td>
            <input 
              name="ntz_referral_settings[short_links]"
              id="ntz_referral_settings[short_links]"
              type="checkbox" value="1" <?php checked( '1', $options['short_links'] ); ?> />
          </td>
        </tr>
<?php if( $this->user_settings['short_links'] ){ ?>
        <tr valign="top">
          <th scope="row">
            <label for="ntz_referral_settings[short_url_base]">Short URL Base</label><br/>
            <small>
              Cum vor arăta link-urile scurtate: 
                <?php echo home_url('/') . ( !empty( $options['short_url_base'] ) ? $options['short_url_base'] : $this->default_url_base ) ; ?>/emag_url<br/>
                <strong>ATENȚIE</strong> înainte de a activa aceast opțiune fii sigur ca fișierul <code>.htaccess</code> are <code>chmod 777</code>
            </small>
          </th>
          <td>
            <input
              type="text"
              name="ntz_referral_settings[short_url_base]" 
              id="ntz_referral_settings[short_url_base]"
              value="<?php echo !empty( $options['short_url_base'] ) ? $options['short_url_base'] : $this->default_url_base; ?>" />
            </td>
        </tr>

        <tr valign="top">
          <th scope="row" style="width:240px">
            <label for="ntz_referral_settings[quick_generator]">Quick Link Generator</label><br/>
          </th>
          <td>
            <input 
              name="ntz_referral_settings[quick_generator]"
              id="ntz_referral_settings[quick_generator]"
              type="checkbox" value="1" <?php checked( '1', $options['quick_generator'] ); ?> />
          </td>
        </tr>
<?php } ?>
        <tr>
          <th colspan="2" style="border-top:1px solid #ccc"></th>
        </tr>

        <tr valign="top">
          <th scope="row"><label for="ntz_referral_settings[share_profit]">Share your profit!</label><br/>
          <small>
            Bifând această opțiune ești de acord să împarți o parte din click-urile tale cu autorul plugin-ului.<br/>
            Această parte este calculată aleator și este luată în considerare doar când funcția <br/>
            <code>&lt;?php <a href="http://php.net/rand" target="_blank">rand</a>(0, 1000) ?&gt;</code> <br/>
            returnează un număr peste 950.
          </small>
          </th>
          <td>
            <input
              name="ntz_referral_settings[share_profit]"
              id="ntz_referral_settings[share_profit]"
              type="checkbox" value="1" <?php checked( '1', $options['share_profit'] ); ?> />
          </td>
        </tr>

      </table>
      <p class="submit">
        <input type="submit" class="button-primary" value="<?php _e('Salvează modificările') ?>" />
      </p>
    </form>

<?php 
  global $wp_rewrite;
  $wp_rewrite->flush_rules();
?>
    <p>În cazul în care funcția de scurtare a link-urilor/redirect nu funcționează, editează fișierul <code>.htaccess</code> folosind un client de FTP și adauga/modifică urmatoarele reguli:</p>
    <p><textarea 
      rows="10"
      class="large-text readonly"
      name="rules"
      id="rules"
      readonly="readonly"
      onclick="this.select()"
      onfocus="this.select()"
      ><?php echo esc_textarea( $wp_rewrite->mod_rewrite_rules() ); ?></textarea></p>

  </div>
<?php 
  } // admin_page

  public function save_settings( $input ){
    $profitshare_id = preg_match( $this->emag_profitshare_regex, $input['profitshare_key'], $profitshare_url );
    $parsedURL = parse_url( $profitshare_url[0] );
    $urlParams = explode( '&', $parsedURL['query'] );
    foreach ( $urlParams as $param ){
      if( strstr( $param, 'ad_client=' ) ){
        $input['profitshare_key'] = str_replace( 'ad_client=','', $param );
      }
    }
    flush_rewrite_rules();
    return $input;
  } // save_settings

  public function quick_generator_menu(){
    $plugin_url = plugin_basename( __FILE__ ) . '?ntz_do=quick_profit';
?>
<script type="text/javascript">
  jQuery(document).ready(function($){
    $('<div id="quickProfitshareWrap"> <input type="text" placeholder="Quick Profitshare" id="quickProfitshareInput" /> </div>').appendTo('body');
    
    $('#quickProfitshareInput').bind('keydown', function(e){
      var t = $(this);
      if( e.keyCode === 13) {
        $.get('<?php echo $plugin_url; ?>',{
          url : escape( this.value )
        }, function(data){
          var result = $('<div class="quickProfithsareResult"/>'),
              close = $('<a href="#" class="close">x</a>');
          close.click(function(){ result.fadeOut(function(){$(this).remove();}); return false; });
          t.val('').blur();
          result
            .append( $('<input />').val(data).click(function(){ $(this).select(); }) )
            .append(close)
          .appendTo('body');
        });
      }
    });
  });
</script>
<style type="text/css" media="screen">
  .quickProfithsareResult,
  #quickProfitshareWrap { position:fixed; bottom:20px; right:20px; padding:5px; -moz-border-radius:5px; border-radius:5px; background:#fff; }
  .quickProfithsareResult { border:1px solid #999; }
  .quickProfithsareResult .close {
    position:absolute;
    right:-10px;
    top:-10px;
    background:#fff;
    font-weight:700;
    color:#c00;
    line-height:1;
    padding:5px 9px;
    -moz-border-radius:20px;
    border-radius:20px;
    text-decoration:none;
    border:1px solid #999;
    -moz-box-shadow:0px 0px 5px rgba(0, 0, 0, .4);
    -webkit-box-shadow:0px 0px 5px rgba(0, 0, 0, .4);
    box-shadow:0px 0px 5px rgba(0, 0, 0, .4);
  }
  .quickProfithsareResult input { width:300px; border:0; font-weight:700; color:#000; }
  #quickProfitshareWrap { background:#333; -moz-box-shadow:0px 0px 2px #fff; -webkit-box-shadow:0px 0px 2px #fff; box-shadow:0px 0px 2px #fff; opacity:0.5; }
  #quickProfitshareWrap:hover { opacity:1; }
  #quickProfitshareInput {
    width:120px;
    -webkit-transition:all 0.5s ease;
    -moz-transition:all 0.5s ease;
    transition:all 0.5s ease;
  }
  #quickProfitshareInput:focus { width:250px; }
</style>
<?php 
  } // quick_generator_menu

/* ================================= */
/* = functii folosite in front end = */
/* ================================= */
  public function replace_links( $content ){
    $profitshare_key = $this->user_settings['profitshare_key'];
    // share your share!
    if( $this->user_settings['share_profit'] == 1 && rand( 0, 1000 ) > 950 ) {
      $profitshare_key = $this->default_profitshare;
    }
    $content = preg_replace( $this->emag_regex, "http://profitshare.emag.ro/click.php?ad_client={$profitshare_key}&amp;redirect=", $content );
    return $content;
  } // replace_links

  public function replace_frontend_links( $content, $really_short = false ){
    global $post;
    preg_match_all( $this->emag_full_url, $content, $profitshare_links );
    
    if( is_array( $profitshare_links[0] ) ){
      foreach( $profitshare_links[0] as $link ){
        if( $this->user_settings['short_links'] ){
          $grab_link_from_db = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT * FROM {$this->db_name} WHERE `url` = %s ", $link ) );

          if( $grab_link_from_db ){ // daca linkul este in db, il afisam;
            $url_shorten = $grab_link_from_db->short_url;
            $url_id      = $grab_link_from_db->id;

          }else { // daca linkul nu este in db, il inseram;
            $url_shorten = preg_replace( $this->emag_regex, '', $link ); // eliminam http://www.emag.ro/
            if( substr( $url_shorten, -1 ) == '/' ){ $url_shorten = substr_replace( $url_shorten, '', -1, 1 ); } // eliminam slash-ul final (just in case)
            $exploded_url = explode( '/', $url_shorten );

            // daca linkul este o categorie (ex. monitoare-lcd) nu facem nici o modificare
            // daca este un produs, ii eliminam categoria si afisam primele 10 caractere
            if( strpos( $url_shorten, '/' ) ){
              $url_shorten = substr( $exploded_url[0], 0, 30 );
            }

            // inseram si curatam linkul in db
            $this->wpdb->insert( $this->db_name,
              array(
                "url"       => $link,
                "short_url" => preg_replace( "#([^a-z0-9\-]+)#i", "", $url_shorten ), // tot ce nu e litera, numar sau cratima dispare
                "hits"      => 0
              ),
              array( "%s", "%s", "%d" )
            );
            $url_id = $this->wpdb->insert_id;
          }
          if( $really_short === true ){
            $url_shorten = '';
          }
          $replaced_url = esc_url( home_url( '/' ) ) . $this->user_settings['short_url_base'] . "/{$url_id}/" . $url_shorten;
          $content = str_replace( $link, $replaced_url, $content );

          // stergem variabilele
          unset( $replaced_url, $url_shorten, $url_id );
        }else {
          $content = str_replace( $link, $this->replace_links( $link ), $content );
        }
      }
    }
    return $content;
  } // replace_frontend_links



  public function redirect(){
    $get_link = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT * FROM {$this->db_name} WHERE `id` = %d", (int)$_GET['url_id'] ) );

    if($get_link){
      // updating hits
      $this->wpdb->update( $this->db_name, array(
        "hits" => $get_link->hits + 1
      ), array(
        "id" => (int)$_GET['url_id']
      ), array( "%d", "%d" ), $where_format = null ); 
    }

    $redirect_url = str_replace( '&amp;', '&', $this->replace_links( $get_link->url ) ); // header location nu functioneaza cu &amp;

    header( "location:" . $redirect_url );
    die();
  } // redirect

  public function quick_profit(){
    if( is_user_logged_in() && current_user_can( 'edit_posts' ) ){
 
      $url = $this->replace_frontend_links( htmlspecialchars( urldecode( $_GET['url'] ), ENT_QUOTES ), true );
      echo $url;
    }
    die();
  } // quick_profit
} // NtzReferral

function ntz_referral_init(){
  $ntz_referral = new NtzReferral();
}
add_action( 'init', 'ntz_referral_init' );

