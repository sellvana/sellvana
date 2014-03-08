<?php

/**
 * Created by pp
 * @project fulleron
 */
class FCom_SampleData_Admin_Controller extends FCom_Admin_Controller_Abstract
{
    public function action_load()
    {
        $xhr    = BRequest::i()->xhr();
        $msg    = "Sample products not imported.";
        $status = 'error';

        try {
            BResponse::i()->startLongResponse();
$t = microtime(true);
            FCom_SampleData_Admin::i()->loadProducts();
echo '<br>'.(microtime(true)-$t);
            $msg    = 'Sample products imported';
            $status = 'success';
        } catch ( Exception $e ) {
echo "<pre>"; print_r($e); echo "</pre>";
            BDebug::logException( $e );
            $msg    = $e->getMessage();
            $status = 'error';
        }
exit;
        if ( !$xhr ) {
            $this->message( $msg, $status );
            BResponse::i()->redirect( 'settings?tab=other' );
        } else {
            $result = array(
                'message' => BLocale::_( $msg ),
                'status'  => $status
            );
            BResponse::i()->json( $result );
        }
    }
}