<?php
/**
 * Created by PhpStorm.
 * User: filipe
 * Date: 2020-02-20
 * Time: 11:52
 */

class Codecia_Dynamicfilter_Model_Cron {

    public function cleanCache() {

        if (Mage::getStoreConfigFlag('codecia_dynamicfilter/configurable_cron/active')) {

            Mage::app()->cleanCache(array('CODECIA_DYNAMICFILTER'));
        }
    }
}