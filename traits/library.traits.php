<?php

trait Library{
    
    public function check( $relevance, $current = 'on' ){
        return (string) $relevance === (string) $current ? true : false ;
    }

/** опции селекта */
    private function __get_option_select($array, $actValue){
        if(!is_array($array)) return;
        
        $option = "";
        
        foreach( $array as $key => $value ){
            $result = selected( $actValue, $key, false );
            
            $attr = array( 'value' => $key );
            if( !empty( $result ) ) $attr['selected'] = 'selected';
            
            $option .= $this->_html( 'option', $attr, $value );
        }
        
        return $option;
    }

    protected function _form_element( $element, $relevance ){
        switch( $element['type'] ){
            case'text':
                $th = $this->_html( 'th', array( 'scope' => 'row' ), $this->_html( 'label', array( 'for' => $element['id'] ), $element['title'] ) );
                
                $content  = $this->_html( 'input', array( 'name' => $element['id'], 'type' => $element['type'], 'id' => $element['id'], 'class' => 'regular-text', 'value' => esc_attr( $relevance ) ) );
                $content .= isset( $element['desc'] ) ? $this->_html( 'p', array( 'class' => 'description' ), $element['desc'] ) : '';
                $td = $this->_html( 'td', $content );
                
                $tr = $this->_html( 'tr', array( 'class' => 'user-last-name-wrap' ), $th . $td );
                    break;
                    
            case'checkbox':
                $th = $this->_html( 'th', array( 'scope' => 'row' ), $element['title'] );
                
                $attr = array( 'name' => $element['id'], 'type' => $element['type'], 'id' => $element['id'], 'value' => 'on' );
                if( $this->check( $relevance ) ) $attr['checked'] = 'checked';
                if( isset( $element['class'] ) ) $attr['class'] = $element['class'];
                
                $input = $this->_html( 'input', $attr ) ;
                $label = isset( $element['desc'] ) ? $this->_html( 'label', array( 'for' => $element['id'] ), $input . ' ' . $element['desc'] ) : $input;
                
                $td = $this->_html( 'td', $label );
                
                $tr = $this->_html( 'tr', array( 'class' => 'user-last-name-wrap' ), $th . $td );
                    break;
            case'select_user':
                $optins = '';
                                                            
                $th = $this->_html( 'th', array( 'scope' => 'row' ), $element['title'] );
                
                foreach( $element['option'] as $user ){
                    $attr = array( 'value' => $user->ID );
                    if( $this->check( $relevance, $user->ID ) ) $attr['selected'] = 'selected';                    
                    $optins .= $this->_html( 'option', $attr, $user->data->display_name );
                } 
                
                $attr = array( 'name' => $element['id'] );
                if( isset( $element['class'] ) ) $attr['class'] = $element['class'];
                
                $select = $this->_html( 'select', $attr, $optins );                
                $select .= isset( $element['desc'] ) ? $this->_html( 'p', array( 'class' => 'description' ), $element['desc'] ) : '';
                $td = $this->_html( 'td', $select );
                                
                $tr = $this->_html( 'tr', array( 'class' => 'user-last-name-wrap' ), $th . $td );
                    
                    break;
            
        }
        
        return $tr;
    }
    
/** Фильтрация  данных, возвращаемые данные $data или error */
    protected function _clear_data( $data, $type="str" ){
        if( !empty( $data ) ){
            switch( $type ){
                case "str": 
                            $data = addcslashes( htmlspecialchars( trim( strip_tags( $data ) ), ENT_QUOTES ), "`" );
                                    break;
                case "int": 
                            $data = abs( ( int ) $data );
                                    break;
                case "log": 
                            $regv ="(^[a-zA-Z0-9_\-]{3,10}$)";
                            $data = ( preg_match( $regv, $data ) ) ? addcslashes( htmlspecialchars( trim( strip_tags( $data ) ), ENT_QUOTES ), "`" ) : 'error';
                                    break;
                case "pas": $regv ="(^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{7,14}$)";
                            $data = ( preg_match( $regv, $data ) ) ? md5( addcslashes( htmlspecialchars( trim( strip_tags( $data ) ), ENT_QUOTES ), "`" ) ) : 'error';
                                    break;
                case "eml":  $regv = '^[^@]+@([a-z\-]+\.)+[a-z]{2,5}$';
                            $data= ( !ereg( $regv, $data ) ) ? 'error' : addcslashes( htmlspecialchars( trim( strip_tags( $data ) ), ENT_QUOTES ), "`" );
                                    break;
            }
        }else{
            $data ='error';
        }
            return $data;
    }
    
/** Для получения случайного значения */
    protected function _random_string( $number, $l = 4 ){
        $array = array( "A", "B", "C", "D", "E", "F", "G",
                        "H", "I", "J", "K", "L", "M", "N",
                        "O", "P", "Q", "R", "S", "T", "U",
                        "V", "W", "X", "Y", "Z", "a", "b",
                        "c", "d", "e", "f", "g", "h", "i",
                        "j", "k", "l", "m", "n", "o", "p",
                        "q", "r", "s", "t", "u", "v", "w",
                        "x", "y", "z", "0", "1", "2", "3",
                        "4", "5", "6", "7", "8", "9" );
        $outstring = '';
        $c = count( $array ) - 1;
        for( $i = 0; $i < $l; $i++ ) rand( 0, $c );
        for( $i = 0; $i < $number; $i++ ){
            $index = rand( 0, $c );
            $outstring .= $array[ $index ];
        }
        return md5( $outstring );
    }

/** Метод создает тег с нужными атрибутами и контентом */
    protected function _html( $tag ) {
    	static $SELF_CLOSING_TAGS = array( 'area', 'base', 'basefont', 'br', 'hr', 'input', 'img', 'link', 'meta' );
    
    	$args = func_get_args();
    
    	$tag = array_shift( $args );
    
    	if ( is_array( $args[0] ) ) {
    		$closing = $tag;
    		$attributes = array_shift( $args );
    		foreach ( $attributes as $key => $value ) {
    			if ( false === $value )
    				continue;
    
    			if ( true === $value )
    				$value = $key;
    
    			$tag .= ' ' . $key . '="' . esc_attr( $value ) . '"';
    		}
    	} else {
    		list( $closing ) = explode( ' ', $tag, 2 );
    	}
    
    	if ( in_array( $closing, $SELF_CLOSING_TAGS ) ) {
    		return "<{$tag} />";
    	}
    
    	$content = implode( '', $args );
    
    	return "<{$tag}>{$content}</{$closing}>";
    }
    
/**
 * Если юзер с правами администратора, то письмо уходит от SUPPORT сайта, если нет то от емайла пользователя
 * @since 0.0.1
 * @param string - Заголовок письма
 * @param string - Тело письма
 * @param string - e-mail получателя
 * @param string - e-mail отправителя
 * @return Возвращает TRUE, если письмо было принято для передачи, иначе FALSE. 
 */
    protected function _send_mail( $title, $message, $to, $from ){

        $headers = 'From: ' . $from . "\r\n" .
                   'Reply-To: ' . $from . "\r\n" .
                   'X-Mailer: PHP/' . phpversion();
        
        return mail($to, $title, $message, $headers);
        
    }
} 