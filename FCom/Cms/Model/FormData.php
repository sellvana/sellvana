<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Cms_Model_FormData extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_cms_form_data';
    static protected $_origClass = __CLASS__;
    /*
    id int unsigned not null auto_increment primary key
    form_name int unsigned
    data_serialized text
    create_dt datetime
    remote_ip varchar(40)
    email varchar(100)
    status varchar(20)
    */
}