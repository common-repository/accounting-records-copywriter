<?php

class Accounting_Records_Copywriter_Internals_AVK{
    protected $_fieldsUser = array();
    
    const ROLE = 'copywriter';
    const SLUG = 'accounting-records-copywriter';
    
    public function __construct(){
        $userArgs = array(
            'blog_id'      => $GLOBALS['blog_id'],
            'role'         => '',
            'meta_key'     => '',
            'meta_value'   => '',
            'meta_compare' => '',
            'meta_query'   => array(),
            'include'      => array(),
            'exclude'      => array(),
            'orderby'      => 'login',
            'order'        => 'ASC',
            'offset'       => '',
            'search'       => '',
            'number'       => '',
            'count_total'  => false,
            'fields'       => 'all',
            'who'          => '',
            'date_query'   => array()
        );
        
        $this->_fieldsUser = array(
                array(
                    'id'    => self::SLUG . '_value_symbol',
                    'type'  => 'text',
                    'class' => 'regular-text',
                    'title' => __( 'Цена услуги', self::SLUG ),
                    'desc'  => __( 'Стоимость за 1000 символов', self::SLUG ),
                    'value' => '50',
                ),
                array(
                    'id'    => self::SLUG . '_characters',
                    'type'  => 'text',
                    'class' => 'regular-text',
                    'title' => __( 'Количество символов', self::SLUG ),
                    'desc'  => __( 'Количество символов, по достижении которого, копирайтер может запросить выплату денег.', self::SLUG ),
                    'value' => '1000'
                ),
                array(
                    'id'    => self::SLUG . '_accounting_whitespace',
                    'type'  => 'checkbox',
                    'title' => __( 'Учет пробелов', self::SLUG ),
                    'desc'  => __( 'Считать пробелы как символы', self::SLUG ),
                ),
                array(
                    'id'     => self::SLUG . '_attachment_records',
                    'type'   => 'select_user',
                    'class'  => self::SLUG . '-attachment-records',
                    'title'  => __( 'Прикрепить запись к пользователю', self::SLUG ) . ':',
                    'option' => get_users( $userArgs ),
                    'desc'   => __( 'Выберете пользователя, к которому будут прикрепляться записи и оправляться на его почту уведомление после подачи заявки на выплату от данного копирайтера.', self::SLUG ),
                )
        );
    }
}