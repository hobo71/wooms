<?php
/**
 * Plugin Name: WooMS
 * Plugin URI: https://wpcraft.ru/product/wooms/
 * Description: Integration for WooCommerce and MoySklad (moysklad.ru, МойСклад) via REST API (wooms)
 * Author: WPCraft
 * Author URI: https://wpcraft.ru/
 * Developer: WPCraft
 * Developer URI: https://wpcraft.ru/
 * Text Domain: wooms
 * Domain Path: /languages
 * WC requires at least: 3.0
 * WC tested up to: 3.5.0
 * PHP requires at least: 5.6
 * WP requires at least: 4.8
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Version: 3.8
 * WooMS XT Latest: 3.8
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Core
 */
class WooMS_Core {

  /**
   * $wooms_version
   */
  public static $wooms_version;

  /**
   * $plugin_file_path
   */
  public static $plugin_file_path;

  /**
   * The init
   */
  public static function init(){


    add_action('plugins_loaded', function(){

      /**
       * Подключение компонентов
       */
      require_once 'inc/class-logger.php';
      require_once 'inc/class-menu-settings.php';
      require_once 'inc/class-menu-tool.php';
      require_once 'inc/class-products-walker.php';
      require_once 'inc/class-import-product-categories.php';
      require_once 'inc/class-import-product-images.php';
      require_once 'inc/class-import-prices.php';
      require_once 'inc/class-hide-old-products.php';

      add_action( 'admin_notices', array(__CLASS__, 'show_notices_35') );

      add_action( 'after_plugin_row_wooms-extra/wooms-extra.php', array(__CLASS__, 'xt_plugin_update_message'), 10, 2 );

      add_filter( "plugin_action_links_" . plugin_basename( __FILE__ ), array(__CLASS__, 'plugin_add_settings_link') );


    });

    // add_action( 'admin_init', array(__CLASS__, 'check_php_and_wp_version') );


    /**
     * Add hook for activate plugin
     * @var [type]
     */
    register_activation_hook( __FILE__, function(){
      do_action('wooms_activate');
    });

    register_deactivation_hook( __FILE__, function(){
      do_action('wooms_deactivate');
    });
  }



  /**
   * Add Settings link in pligins list
   */
  public static function plugin_add_settings_link( $links ) {
    $settings_link = '<a href="admin.php?page=mss-settings">Настройки</a>';
    $xt_link = '<a href="//wpcraft.ru/product/wooms-xt/" target="_blank">Расширенная версия</a>';
    array_unshift($links, $xt_link);
    array_unshift($links, $settings_link);
    return $links;
  }


  /**
   * Проверяем актуальность расширенной версии и сообщаем если есть обновления
   * Проверка происходит на базе данных в комментарии базовой версии
   */
  public static function xt_plugin_update_message( $data, $response ) {


    $data = get_file_data( __FILE__, array('xt_version' => 'WooMS XT Latest') );
    $xt_version_remote = $data['xt_version'];

    // $data = get_file_data( __FILE__, array('xt_version' => 'WooMS XT Latest') );
    $data = get_plugin_data( plugin_dir_path( __DIR__ ) . "wooms-extra/wooms-extra.php", false, false );
    $xt_version_local = $data['Version'];
    // $data = plugin_dir_path( __DIR__ );

    $check = version_compare( $xt_version_local, $xt_version_remote, '>=' );


    if($check){
      return;
    }
    $wp_list_table = _get_list_table( 'WP_Plugins_List_Table' );

    printf(
      '<tr class="plugin-update-tr">
        <td colspan="%s" class="plugin-update update-message notice inline notice-warning notice-alt">
          <div class="update-message">
            <span>Вышла новая версия плагина WooMS XT: %s. Скачать обновление можно в консоли: <a href="https://wpcraft.ru/my" target="_blank">https://wpcraft.ru/my</a></span>
          </div>
        </td>
      </tr>',
      $wp_list_table->get_column_count(),
      $xt_version_remote
    );

  }



  /**
   * Вывод сообщения в консоли
   */
  public static function show_notices_35() {

    if(is_plugin_active( 'wooms-extra/wooms-extra.php' )){
      $data = get_plugin_data( plugin_dir_path( __DIR__ ) . "wooms-extra/wooms-extra.php", false, false );
      if(empty($data['Version'])){
        return;
      }

      $xt_version_local = $data['Version'];
      // $data = plugin_dir_path( __DIR__ );


      $check = version_compare( $xt_version_local, '3.5', '>=' );

      if($check){
        return;
      }
      ?>
      <div class="notice notice-error">
        <p>
          <strong>Плагин WooMS XT нужно срочно обновить до версии 3.5! </strong>
          <a href="https://wpcraft.ru/my">https://wpcraft.ru/my</a>
        </p>
      </div>
      <?php
    }

    return;

    //@TODO - переписать эту часть чтобы без транзита работала
    self::$wooms_version = get_file_data( __FILE__, array('wooms_ver' => 'Version') );

    $message = get_transient( 'wooms_activation_error_message' );
    if ( ! empty( $message ) ) {
      echo '<div class="notice notice-error">
              <p><strong>Плагин WooMS не активирован!</strong> ' . $message . '</p>
          </div>';
      delete_transient( 'wooms_activation_error_message' );
    }
  }

  /**
   * check_php_and_wp_version
   */
  public static function check_php_and_wp_version() {
    global $wp_version;

    $wooms_version = get_file_data( __FILE__, array('wooms_ver' => 'Version') );

    define( 'WOOMS_PLUGIN_VER', $wooms_version['wooms_ver'] );

    $php       = 5.6;
    $wp        = 4.7;
    $php_check = version_compare( PHP_VERSION, $php, '<' );
    $wp_check  = version_compare( $wp_version, $wp, '<' );

    if ( $php_check ) {
      $flag = 'PHP';
    } elseif ( $wp_check ) {
      $flag = 'WordPress';
    }

    if ( $php_check || $wp_check ) {
      $version = 'PHP' == $flag ? $php : $wp;
      if ( ! function_exists( 'deactivate_plugins' ) ) {
        require_once ABSPATH . '/wp-admin/includes/plugin.php';
      }

      deactivate_plugins( plugin_basename( __FILE__ ) );
      if ( isset( $_GET['activate'] ) ) {
        unset( $_GET['activate'] );
      }

      $error_text = sprintf( 'Для корректной работы плагин требует версию <strong>%s %s</strong> или выше.', $flag, $version );
      set_transient( 'wooms_activation_error_message', $error_text, 60 );

    } elseif ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {

      if ( ! function_exists( 'deactivate_plugins' ) ) {
        require_once ABSPATH . '/wp-admin/includes/plugin.php';
      }

      deactivate_plugins( plugin_basename( __FILE__ ) );
      if ( isset( $_GET['activate'] ) ) {
        unset( $_GET['activate'] );
      }

      $error_text = sprintf( 'Для работы плагина WooMS требуется плагин <strong><a href="//wordpress.org/plugins/woocommerce/" target="_blank">%s %s</a></strong> или выше.', 'WooCommerce', '3.0' );
      set_transient( 'wooms_activation_error_message', $error_text, 60 );
    }
  }
}

WooMS_Core::init();






/**
 * Helper function for get data from moysklad.ru
 */
function wooms_get_data_by_url( $url = '' ) {

  if ( empty( $url ) ) {
    return false;
  }

  $base64_string = base64_encode( get_option( 'woomss_login' ) . ':' . get_option( 'woomss_pass' ) );
  $args = array(
    'timeout' => 45,
    'headers' => array(
      'Authorization' => 'Basic ' . $base64_string,
    ),
  );

  $response = wp_remote_get( $url, $args );
  if ( is_wp_error( $response ) ) {
    set_transient( 'wooms_error_background', $response->get_error_message() );

    return false;
  }
  if ( empty( $response['body'] ) ) {
    set_transient( 'wooms_error_background', "REST API вернулся без требуемых данных" );

    return false;
  }
  $data = json_decode( $response['body'], true );
  if ( empty( $data ) ) {
    set_transient( 'wooms_error_background', "REST API вернулся без JSON данных" );

    return false;
  } else {
    return $data;
  }
}

/**
 * Helper new function for responses data from moysklad.ru
 *
 * @param string $url
 * @param array $data
 * @param string $type
 *
 * @return array|bool|mixed|object
 */
function wooms_request( $url = '', $data = array(), $type = 'GET' ) {
  if ( empty( $url ) ) {
    return false;
  }

  if ( isset( $data ) && ! empty( $data ) && 'GET' == $type ) {
    $type = 'POST';
  }
  if ( 'GET' == $type ) {
    $data = null;
  } else {
    $data = json_encode( $data );
  }

    $args = array(
    'method'      => $type,
    'timeout'     => 45,
    'redirection' => 5,
    'headers'     => array(
      "Content-Type"  => 'application/json',
      'Authorization' => 'Basic ' .
                         base64_encode( get_option( 'woomss_login' ) . ':' . get_option( 'woomss_pass' ) ),
    ),
    'body'        => $data,
  );

  $request = wp_remote_request( $url, $args);
  if ( is_wp_error( $request ) ) {
    set_transient( 'wooms_error_background', $request->get_error_message() );
    do_action(
      'wooms_logger',
      $type = 'error_request_api',
      $title = 'Ошибка REST API',
      $desc = $request->get_error_message()
    );

    return false;
  }
  if ( empty( $request['body'] ) ) {
    set_transient( 'wooms_error_background', "REST API вернулся без требуемых данных" );
    do_action(
      'wooms_logger',
      $type = 'error_request_api',
      $title = 'REST API вернулся без требуемых данных',
      $desc = ''
    );

    return false;
  }
  $response = json_decode( $request['body'], true );

  return $response;
}

/**
 * Get product id by UUID from metafield
 * or false
 */
function wooms_get_product_id_by_uuid( $uuid ) {

  $posts = get_posts( 'post_type=product&meta_key=wooms_id&meta_value=' . $uuid );
  if ( empty( $posts[0]->ID ) ) {
    return false;
  } else {
    return $posts[0]->ID;
  }
}
