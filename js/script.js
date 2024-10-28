(function( $ ){
    if ( !$ ) return;
    
    $( document ).ready(function(){
        $( 'body' ).on( 'click', '.payment-order', function(){
            b = $( this );
            b.attr( 'disabled', true );
            var data = {
            		action: 'avk_arc_query',
                    action_arc_avk: b.attr( 'data-value' ),
                    user_id_arc_avk: b.attr( 'data-user-id' ),
                    avk_notice_arc: accountingRecordsCopywriter.safety
            	};
        	$.ajaxSetup({cache: false});
            
        	$.post( ajaxurl, data, function( response ){
                //console.log( response );
                response = JSON.parse( response );
                //console.log( response );
                if( typeof response.result == 'undefined' ) return false;
                
                switch( response.action ){
                    case'order':
                        if( response.result ){
                            b.parents( '#TB_ajaxContent' ).html( '<p>' + response.msg + '</p>' );
                        }else{
                            b.parent().prev( '.msg-content' ).html( '<p>' + response.msg + '</p>' );
                        }
                            break;
                    case'confirmation':
                        if( response.result ){
                            b.parents( '#TB_ajaxContent' ).html( '<p>' + response.msg + '</p>' );
                        }else{
                            b.parents( '#TB_ajaxContent' ).html( '<p>' + response.msg + '</p>' );
                        }
                            break;
                }
                
                
        	});
            return false;
        });
        
        $( '#role' ).on( 'change', function(){
            //console.log( $( this ).attr( 'value' ) );
            if( 'copywriter' == $( this ).attr( 'value' ) ){
                $( '#copywriter_block' ).slideDown( 500 );
            }else{
                $( '#copywriter_block' ).slideUp( 500 );
            }
        });
        
        if(  'copywriter' != $( '#role' ).attr( 'value' ) ){
            $( '#copywriter_block' ).slideUp( 50 );
        }
    });
})( jQuery );