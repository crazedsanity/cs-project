<?php
/*
 * Created on Aug 23, 2007
 * 
 * SVN INFORMATION:::
 * -------------------
 * Last Author::::::::: $Author$ 
 * Current Revision:::: $Revision$ 
 * Repository Location: $HeadURL$ 
 * Last Updated:::::::: $Date$
 */




//#######################################################################################
/**
 * Built to avoid always printing-out the results (so we can retrieve result data separately.
 */
class MyDisplay extends SimpleReporter {
    
    function paintHeader($test_name) {
    }
    
    function paintFooter($test_name) {
    }
    
    function paintStart($test_name, $size) {
        parent::paintStart($test_name, $size);
    }
    
    function paintEnd($test_name, $size) {
        parent::paintEnd($test_name, $size);
    }
    
    function paintPass($message) {
        parent::paintPass($message);
    }
    
    function paintFail($message) {
        parent::paintFail($message);
    }
}
//#######################################################################################

?>
