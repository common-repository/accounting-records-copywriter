<?php
/** 
 *  Plugin Name: Accounting Records Copywriter
 * 	Plugin URI: http://avkproject.ru/plugins/accounting-records-copywriter.html
 *  Description: Плагин учета записей написанных копирайтером/рерайтером.
 * 	Author: Smiling_Hemp
 * 	Version: 1.0.0
 * 	Author URI: https://profiles.wordpress.org/smiling_hemp#content-plugins
 */
 
/**
    Copyright (C) 20013-2015 Smiling_Hemp, avkproject.ru (support AT avkproject DOT ru)
    
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 3 of the License, or
    (at your option) any later version.
    
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    
    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

include_once('traits/library.traits.php');
include_once('classes/internals.class.php');
 
class Accounting_Records_Copywriter_AVK extends Accounting_Records_Copywriter_Internals_AVK{
    
    use Library;
    
    public $userId;
    public $userRole;
    public $userEmail;
    public $userName;
    
    protected $_modalWindow;
    protected $_pluginBase;
    protected $_pluginUrl;
    protected $_pluginPath;
    
    public function __construct(){
        
        $this->_pluginBase = basename( __DIR__ ) . '/' . basename( __FILE__ );
        $this->_pluginUrl  = plugin_dir_url( __FILE__ );
        $this->_pluginPath = plugin_dir_path( __FILE__ );
        
    /** Подключение файлов с переводом */
        $this->__load_language_plugin();
        
        add_action( 'admin_init', array( &$this, 'set_value_for_plugin' ) );
        
        parent::__construct();
        
    /** Действия при активации и деактивации плагина */   
        register_activation_hook( __FILE__, array( &$this, 'register_plugin' ) );
        register_deactivation_hook( __FILE__, array( &$this, 'deregister_plugin' ) );
        
        add_filter( 'plugin_row_meta', array( &$this, 'add_link_dashplugins'), 10, 2);
        
        add_action( 'wp_ajax_avk_arc_query', array( &$this, 'ajax_query' ) );
        
    /** Добавляет ссылку настроек */
        //add_filter( 'plugin_action_links',  array( &$this, 'add_link_tools' ), 10, 2);
        
        //add_action( 'admin_menu', array( &$this, 'add_page_admin_menu' ) );
        
        add_action( 'post_updated', array( &$this, 'save_post_and_page' ), 10, 3 );
        
        add_action( 'user_new_form', array( &$this, 'add_fields_new_user' ), 10, 1 );
        add_action( 'edit_user_profile', array( &$this, 'add_fields_new_user' ), 10, 1 );
        add_action( 'user_register', array( &$this, 'register_new_user' ), 10, 1 );
        add_action( 'edit_user_profile_update', array( &$this, 'register_new_user' ), 10, 1 );
        
        add_action( 'personal_options', array( &$this, 'add_personal_options' ), 10, 1 );
        
        add_action( 'admin_print_scripts-edit.php',      array( &$this, 'engen_user_edit_script' ) );
        add_action( 'admin_print_scripts-users.php',     array( &$this, 'engen_user_edit_script' ) );
        add_action( 'admin_print_scripts-user-new.php',  array( &$this, 'engen_new_user_script' ) );
        add_action( 'admin_print_scripts-user-edit.php', array( &$this, 'engen_user_edit_script' ) );
        add_action( 'admin_print_scripts-profile.php',   array( &$this, 'engen_user_edit_script' ) );
        
        add_action( 'user_row_actions', array( &$this, 'add_user_action' ), 10, 2 );
        add_filter( 'manage_posts_columns', array( &$this, 'add_name_column' ), 10, 1 );
        add_filter( 'manage_pages_columns', array( &$this, 'add_name_column' ), 10, 1 );
        add_action( 'manage_posts_custom_column', array( &$this, 'add_value_name_column' ), 10, 2 );
        add_action( 'manage_pages_custom_column', array( &$this, 'add_value_name_column' ), 10, 2 );
        add_action( 'manage_users_columns', array( &$this, 'add_new_user_column' ), 10, 1 );
        add_action( 'manage_users_custom_column', array( &$this, 'output_new_user_column' ), 10, 3 );
    }
    
    public function add_name_column( $nameColums ){
        global $pagenow;
        if( $pagenow == 'edit.php' && self::ROLE == $this->userRole )
            $nameColums['characters'] = __( 'Количество символов', self::SLUG );
        return $nameColums;
    }
    
    public function add_value_name_column( $column, $postId ){
        if( $column == 'characters' ){
            $characters = get_post_meta( $postId, '_' . self::SLUG . '_length_content', true );
            
            echo $characters;
        }
    }
    
    public function add_user_action( $action, $userObj ){
        global $pagenow;
        $orderPayment = get_user_meta( $userObj->ID, '_' . self::SLUG . '_the_order_for_payment', true );
        if( $pagenow == 'users.php' && $this->userRole == 'administrator' && !empty( $orderPayment ) ){
            $button = $this->_html( 'input', array( 'alt'          => '#TB_inline?height=200&width=350&inlineId=info-user-' . $userObj->ID,
                                                    'title'        => __( 'Выплата копирайтеру', self::SLUG ) . ' ' . $userObj->data->display_name,
                                                    'type'         => 'button',
                                                    'class'        => 'thickbox thickbox_acreco button action',
                                                    'value'        => __( 'Заказал выплату', self::SLUG ) ) );
            
            $tr = '';
            foreach( $orderPayment as $id => $characters ){
                $post = get_post( $id );
                
                $th = $this->_html( 'td', $post->post_title );
                $td = $this->_html( 'td', get_post_type_object( $post->post_type )->labels->singular_name );
                
                $tr .= $this->_html( 'tr', $th . $td );
                
                $allCharacter += $characters;
            }

            $summa = ( $allCharacter * (int) get_user_meta( $userObj->ID, '_' . self::SLUG . '_value_symbol', true ) ) / 1000;

            $th = $this->_html( 'th', __( 'Всего:', self::SLUG ) );
            $td = $this->_html( 'td', $allCharacter . ' ' . __( 'символа(ов)', self::SLUG ) );
            $tr .= $this->_html( 'tr', $th . $td );
            
            $th = $this->_html( 'th', __( 'Итого к оплате:', self::SLUG ) );
            $td = $this->_html( 'td', round( $summa, 2 ) . ' ' . __( 'у.е.', self::SLUG ) );
            $tr .= $this->_html( 'tr', $th . $td );
            
            $table = $this->_html( 'table', array( 'class' => 'arc-order-payment' ), $tr );
            
            $attr = array( 'type'         => 'button',
                           'id'           => 'payment-order-' . $userObj->ID,
                           'class'        => 'button button-primary payment-order',
                           'data-value'   => 'paid_for_by_the_payment_order',
                           'data-user-id' => $userObj->ID,
                           'value'        => __( 'Оплачено', self::SLUG ) );
        
            $button2 = '<p style="text-align: center">' . $this->_html( 'input', $attr ) . '</p>';
            
            $div = '<div id="info-user-' . $userObj->ID . '" class="modal_window">' . $table . $button2 . '</div>';
            
            $newAction = array( 'avk_arc' => $button . $div, 'werr' => get_userdata( $userObj->ID )->email );
            
            $action = array_merge( $newAction, $action );
        }
        
        return $action;
    }

    public function add_new_user_column( $columnName ){
        global $pagenow;
        if( $pagenow == 'users.php' && isset( $_GET['role'] ) && $_GET['role'] == self::ROLE ){
            $columnName['characters'] = __( 'Всего написано', self::SLUG );
        }
        return $columnName;
    }

    public function output_new_user_column( $value, $columnName, $userId ){
        global $pagenow;
        if( $pagenow == 'users.php' && isset( $_GET['role'] ) && $_GET['role'] == self::ROLE && $columnName == 'characters' ){
            $pageC = $postC = $character = $otherTypes = 0;
            $arrayPosts  = get_user_meta( $userId, '_' . self::SLUG . '_the_paid_payment_order', true );

            if( !is_array( $arrayPosts ) ) return __( 'Нет записей', self::SLUG);
            foreach( $arrayPosts as $id => $characters ){
                $post = get_post( $id );
                switch( $post->post_type ){
                    case'post':
                        $postC += 1;
                            break;
                    case'page':
                        $pageC += 1;
                            break;
                    default: 
                        $otherTypes += 1;
                }
                $character += $characters;
            }
            
            $textPost = $postC ? sprintf( _n( '%s Post', '%s Posts', $postC ), $postC ) . '<br />' : '';
            $textPage = $pageC ? sprintf( _n( '%s Page', '%s Pages', $pageC ), $pageC ) . '<br />' : '';
            $textOtherTypes = $otherTypes ? sprintf( _n( '%s Post', '%s Posts', $otherTypes ) . ' другова типа', $otherTypes ) . '<br />' : '';
            
            $string = $textPost . $textPage . $textOtherTypes . ' Всего символов - ' . $character;
            return $string;
        }
    }
    
    public function add_personal_options( $profileUser ){
        if( $profileUser->roles[0] != self::ROLE ) return;
        
        $arrayPosts  = get_user_meta( $profileUser->ID, '_' . self::SLUG . '_length_content', true );
        
        if( !is_array( $arrayPosts ) ) return;
        
        $postC = $pageC = $otherTypes = $postCharacter = $pageCharacter = $otherTypesCharacter = $allCharacter = 0;
        
        foreach( $arrayPosts as $id => $characters ){
            $post = get_post( $id );
            
            switch( $post->post_type ){
                case'post':
                    $postC += 1;
                    $postCharacter += $characters;
                        break;
                case'page':
                    $pageC += 1;
                    $pageCharacter += $characters;
                        break;
                default: 
                    $otherTypes += 1;
                    $otherTypesCharacter += $characters;
            }
            
            $allCharacter += $characters;
        }
        
        $ul = $li = $button = '';
        if( $postC ){
            $temp = sprintf( _n( '%s Post', '%s Posts', $postC ), $postC ) . ' &ndash; ' . $postCharacter . '&nbsp;' . __( 'символа(ов)', self::SLUG );
            $p = $this->_html( 'p', array( 'class' => 'description' ), $temp );
            $li .= $this->_html( 'li', $p );
        }
        if( $pageC ){
            $temp = sprintf( _n( '%s Page', '%s Pages', $pageC ), $pageC ) . ' &ndash; ' . $pageCharacter . '&nbsp;' . __( 'символа(ов)', self::SLUG );
            $p = $this->_html( 'p', array( 'class' => 'description' ), $temp );
            $li .= $this->_html( 'li', $p );
        }
        if( $otherTypes ){
            $temp = sprintf( _n( '%s Post', '%s Posts', $otherTypes ), $otherTypes ) . ' ' . __( 'другова типа', self::SLUG ) . ' &ndash; ' . $otherTypesCharacter . '&nbsp;' . __( 'символа(ов)', self::SLUG );
            $p = $this->_html( 'p', array( 'class' => 'description' ), $temp );
            $li .= $this->_html( 'li', $p );
        }
                
        if( !empty( $li ) ) $ul = $this->_html( 'ul', $li . $this->_html( 'li', __( 'Итого', self::SLUG ) . ':&nbsp;&nbsp;' . $allCharacter . ' ' . __( 'символа(ов)', self::SLUG ) ) );
        
        if( (int) $allCharacter > (int) get_user_meta( $profileUser->ID, '_' . self::SLUG . '_characters', true ) && $this->userRole == self::ROLE && IS_PROFILE_PAGE ){
            $button = $this->get_b( $profileUser );
        }
        $th = $this->_html( 'th', array( 'scope' => 'row', 'class' => 'class-cop-title' ), __( 'Написано на данный момент', self::SLUG ) );
        $td = $this->_html( 'td', !empty( $ul ) ? $ul . $button : __( 'У вас нет написанных статей', self::SLUG ) );
        
        $tr = $this->_html( 'tr', $th . $td );
        echo $tr;
    }
    
    protected function get_b( $user ){
        $attr = array( 'alt'          => '#TB_inline?height=170&width=350&inlineId=info-user-' . $user->ID,
                       'title'        => __( 'Заказ выплаты денег', self::SLUG ),
                       'type'         => 'button',
                       'class'        => 'thickbox thickbox_acreco button action',
                       'value'        => __( 'Заказать выплату', self::SLUG ) );
        
        $buttonOpenModale = $this->_html( 'input', $attr );
        
        $h2 = $this->_html( 'h2', __( 'Внимание', self::SLUG ) . '!' );
        $p = $this->_html( 'p', __( 'После подачи заявки, вы не сможете вносить какие либо изменения в данные записи.', self::SLUG ) );
        
        $attr = array( 'type'         => 'button',
                       'id'           => 'payment-order-' . $user->ID,
                       'class'        => 'button button-primary payment-order',
                       'data-value'   => 'order_for_payment',
                       'data-user-id' => $user->ID,
                       'value'        => __( 'Продолжить', self::SLUG ) );
        
        $button = '<p style="text-align: center">' . $this->_html( 'input', $attr ) . '</p>';
        $msgCon = $this->_html( 'div', array( 'class' => 'msg-content' ), $h2 . $p );
        $this->_modalWindow = $this->_html( 'div', array( 'id' => 'info-user-' . $user->ID, 'class' => 'modal_window' ), $msgCon . $button );
        
        add_action( 'admin_footer-profile.php', function(){ echo $this->_modalWindow; } );
        
        return $buttonOpenModale;
    }
    
    public function engen_new_user_script(){
        wp_enqueue_script( self::SLUG . '-script-edit', $this->_pluginUrl . 'js/script.js', array( 'jquery' ), '1.0.0' );
    }
    
    public function engen_user_edit_script(){
        add_thickbox();
        
        wp_enqueue_script( self::SLUG . '-script-edit', $this->_pluginUrl . 'js/script.js', array( 'jquery' ), '1.0.0' );
        wp_localize_script( self::SLUG . '-script-edit', 'accountingRecordsCopywriter', array( 'safety' => wp_create_nonce( self::SLUG . '_' . SECURE_AUTH_KEY ) ) );
        
        wp_enqueue_style  ( self::SLUG . '-style-edit', $this->_pluginUrl . 'css/style.css', array(), '1.0.0' );
    }
    
    private function __the_order_for_payment(){
        global $wpdb;
        
        if( $this->userRole != self::ROLE ){
            $result = array( 'result' => false, 'msg' => __( 'У вас не достаточно прав для совершении данного действия', self::SLUG ) );
            return $result;
        }
        
        $arrayPosts  = get_user_meta( $this->userId, '_' . self::SLUG . '_length_content', true );
        
        if( !is_array( $arrayPosts ) ){
            $result = array( 'result' => false, 'msg' => __( 'Не удалось получить данные!', self::SLUG ) );
            return $result;
        }
        
        $allCharacter = 0;
        
        foreach( $arrayPosts as $characters ) $allCharacter += $characters;
        
        if( (int) $allCharacter < (int) get_user_meta( $profileUser->ID, '_' . self::SLUG . '_characters', true ) ){
            $result = array( 'result' => false, 'msg' => __( 'Малое количество символов!', self::SLUG ) );
            return $result;
        }
        
        $userId = (int) get_user_meta( $this->userId, '_' . self::SLUG . '_attachment_records', true );
        
        $sql = 'UPDATE ' . $wpdb->posts . ' SET post_author=%d WHERE post_author=%d';
        $result = $wpdb->query( $wpdb->prepare( $sql, $userId, $this->userId ) );
        
        if( !$result ){
            $result = array( 'result' => false, 'msg' => __( 'Не удалось изменить записи, сообщите об этом администратору!', self::SLUG ) );
            return $result;
        }
        
        delete_user_meta( $this->userId, '_' . self::SLUG . '_length_content' );
        add_user_meta( $this->userId, '_' . self::SLUG . '_length_content', array() );
        
        $orderPayment = get_user_meta( $this->userId, '_' . self::SLUG . '_the_order_for_payment', true );
        
        if( !empty( $orderPayment ) ) $arrayPosts = $orderPayment + $arrayPosts;
        
        update_user_meta( $this->userId, '_' . self::SLUG . '_the_order_for_payment', $arrayPosts );
        
        $summa = ( $allCharacter * (int) get_user_meta( $this->userId, '_' . self::SLUG . '_value_symbol', true ) ) / 1000;
        $title = __( 'Вам поступила заявка на выплату', self::SLUG );
        $message =  sprintf( __( 'Копирайтер %s (E-mail: %s ) запросил выплату денег, на сумму %d у.е.', self::SLUG ), $this->userName, $this->userEmail, $summa );
        $to = get_userdata( $userId )->data->user_email;
        $from = $this->userEmail;
        
        $this->_send_mail( $title, $message, $to, $from );
        
        $result = array( 'result' => true, 'msg' => __( 'Ваша заявка подана и находится на стадии рассмотрения. Как только она будет рассмотрена вам придёт сообщение на ваш e-mail.', self::SLUG ) );
        return $result;
    }
    
    private function __the_paid_payment_order(){
        if( $_POST['user_id_arc_avk'] == 'undefined' ){
            $result = array( 'result' => false, 'msg' => __( 'Недостаточно данных! Ошибка №', self::SLUG ) . __LINE__ );
            return $result;
        }
        $userId = $this->_clear_data( $_POST['user_id_arc_avk'], 'int' );
        $orderPayment = get_user_meta( $userId, '_' . self::SLUG . '_the_order_for_payment', true );
        
        if( empty( $orderPayment ) ){
            $result = array( 'result' => false, 'msg' => __( 'Не удалось получить данные! Ошибка №', self::SLUG ) . __LINE__ );
            return $result;
        }
        
        $arrayPosts = get_user_meta( $userId, '_' . self::SLUG . '_the_paid_payment_order', true );
        if( !empty( $arrayPosts ) ) $orderPayment = $orderPayment + $arrayPosts;
        
        $resultUpdate = update_user_meta( $userId, '_' . self::SLUG . '_the_paid_payment_order', $orderPayment );
        
        if( $resultUpdate ){
            delete_user_meta( $userId, '_' . self::SLUG . '_the_order_for_payment' );
            $result = array( 'result' => true, 'msg' => __( 'Данные успешно обновлены!', self::SLUG ) );
        }else{
            $result = array( 'result' => false, 'msg' => __( 'Данные не обновлены, попробуйте еще раз! Ошибка №', self::SLUG ) . __LINE__ );
        }
        return $result;
    }
    
    public function ajax_query(){
        check_ajax_referer(self::SLUG.'_'.SECURE_AUTH_KEY, 'avk_notice_arc');
        
        if( isset( $_POST['action_arc_avk'] ) && $_POST['action_arc_avk'] == 'order_for_payment' ){
            $result = $this->__the_order_for_payment();
            $result['action'] = 'order';
        }
            
        if( isset( $_POST['action_arc_avk'] ) && $_POST['action_arc_avk'] == 'paid_for_by_the_payment_order' ){
            $result = $this->__the_paid_payment_order();
            $result['action'] = 'confirmation';
        }
        
        $result = json_encode( $result );
        exit( $result );
    }
    
    public function save_post_and_page( $postID ){
        if( $this->userRole != self::ROLE ) return;
        if( $_SERVER['REQUEST_METHOD'] == 'POST' && isset( $_POST['content'] ) ){
            if( 'on' == get_user_meta( $this->userId, '_' . self::SLUG . '_accounting_whitespace', true ) ){
                $string = trim( strip_tags( $_POST['content'] ) );
            }else{
                $string = str_replace( ' ', '', trim( strip_tags( $_POST['content'] ) ) );
            }
            $length = strlen( $string );
            
            $value  = get_user_meta( $this->userId, '_' . self::SLUG . '_length_content', true );
            
            if( is_array( $value ) ){
                $value[ $postID ] = $length;
            }else{
                $value = array( $postID => $length );
            }
            
            delete_post_meta( $postID, '_' . self::SLUG . '_length_content' );
            add_post_meta( $postID, '_' . self::SLUG . '_length_content', $length );
            
            delete_user_meta( $this->userId, '_' . self::SLUG . '_length_content' );
            add_user_meta( $this->userId, '_' . self::SLUG . '_length_content', $value );
        }
    }
     
    public function add_fields_new_user( $data ){
        global $pagenow;
        $tr = '';
        if( is_object( $data ) ){
            
            if( $data->roles[0] != self::ROLE ) return;
            
            foreach( $this->_fieldsUser as $index => $element ){
                $tr .= $this->_form_element( $element, get_user_meta( $data->ID, '_' . $element['id'], true ) );
            }
            
        }else{
            
            if( $data != 'add-new-user') return;
            
            foreach( $this->_fieldsUser as $index => $element ){
                $value = $_SERVER['REQUEST_METHOD'] == 'POST' && isset( $_POST['createuser'], $_POST[ $element['id'] ] ) ? wp_unslash( $_POST[$arrayValue['id']] ) : isset( $element['value'] ) ? $element['value'] : '' ;
                $tr .= $this->_form_element( $element, $value );
            }
            
        }
        
        $table = $this->_html( 'table', array( 'class' => 'form-table' ), $tr );
        
        $block = $this->_html( 'h3', sprintf( __( '%s Настройки копирайтера', self::SLUG ), $this->_html( 'span', array( 'class' => "dashicons dashicons-copy-avk dashicons-http", '' ) ) ) ) . $table;
        
        echo $this->_html( 'div', array( 'id' => 'copywriter_block' ), $block );
    }
    
    public function set_value_for_plugin(){
        $user = wp_get_current_user();
        $this->userRole  = $user->roles[0];
        $this->userId    = $user->ID;
        $this->userEmail = $user->data->user_email;
        $this->userName  = $user->data->display_name;
    }
    
    public function register_new_user( $userId ){
        global $pagenow;
        
        if( $_SERVER['REQUEST_METHOD'] != 'POST' ) return;
        
        foreach( $this->_fieldsUser as $arrayValue ){
            if( isset( $_POST[ $arrayValue['id'] ] ) && ( $_POST['role'] == self::ROLE ) && !empty( $_POST[ $arrayValue['id'] ] ) ){
                $userMeta = $this->_clear_data( $_POST[ $arrayValue['id'] ] );
                delete_user_meta( $userId, '_' . $arrayValue['id'] );
                add_user_meta( $userId, '_' . $arrayValue['id'], $userMeta );
            }else if( $arrayValue['type'] == 'checkbox' ){
                delete_user_meta( $userId, '_' . $arrayValue['id'] );
                add_user_meta( $userId, '_' . $arrayValue['id'], 'off' );
            }
        }
        
    }
    
    public function add_page_admin_menu(){
        $includPage = array();
        
        $includPage[] = add_submenu_page( 'options-general.php', __( 'Настройки для копирайтера', self::SLUG ), __( 'Копирайтер', self::SLUG ), 'manage_options', self::SLUG . '-settings', array( &$this, 'get_page' ) );
        
        foreach( $includPage as $page ){
            add_action( 'admin_print_scripts-' . $page, array( &$this, 'engen_plugin_script_admin' ) );
        }
    }
    
    public function get_page(){
        if( !isset( $_GET[ 'page' ] ) ) return;
        $page = $this->_clear_data( $_GET[ 'page' ] );
        switch( $page ){
            case self::SLUG . '-settings':
                        include_once $this->_pluginPath . 'pages/main_settings.php';
                                    break;
        }
    }
    
    public function engen_plugin_script_admin(){
        
    }
    
/** Добавляет ссылку настроек */
    public function add_link_tools( $links, $file ){
		if ( $file == $this->_pluginBase ){
			$settingsLink = '<a href="' . admin_url() . '">' . __( 'Настройки', self::SLUG ) . '</a>';
			array_unshift( $links, $settingsLink );
        }
		return $links;
    }

/** Действия при активации плагина */
    public function register_plugin(){
        if( version_compare( PHP_VERSION, '5.4.0', '<' ) ) {
            deactivate_plugins( $this->_pluginBase );
			wp_die( sprintf( __( 'Этот плагин требует версию %s Вернуться обратно', self::SLUG ), 'PHP >= 5.4.0. <a href="' . admin_url( 'plugins.php' ) . '"><strong>' ) . '</strong></a>' );
        }
        
        add_role( self::ROLE, __( 'Копирайтер', self::SLUG ), array( 'read'                 => true, 
                                                                     'edit_posts'           => true,
                                                                     'publish_posts'        => true, 
                                                                     'edit_published_posts' => true, 
                                                                     'upload_files'         => true,
                                                                     'edit_pages'           => true,
                                                                     'edit_published_pages' => true,
                                                                     'edit_pages'           => true,
                                                                     'publish_pages'        => true,
                                                                     'edit_published_pages' => true ) );
    }

/** Действия при деактивации плагина */
    public function deregister_plugin(){
        remove_role( self::ROLE );
    }
    
/** Подключение файлов с переводом */
    private function __load_language_plugin(){
        load_plugin_textdomain( self::SLUG, false, dirname( plugin_basename( __FILE__ ) ) . '/lang');
    }

/** Добавляет ссылки на странице плагинов */
    public static function add_link_dashplugins( $links, $file ){        
		if( $file == plugin_basename( __FILE__ ) ) {
			$links[] = '<a href="https://goo.gl/cCtMON">' . __( 'Поблагодарить', self::SLUG ) . '</a>';
            //$links[] = '<a href="#" target="_blank">F.A.Q.</a>';
		}
		return $links;
	}
}
 
new Accounting_Records_Copywriter_AVK;