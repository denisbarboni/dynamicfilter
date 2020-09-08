<?php
/**
 * Created by PhpStorm.
 * User: filipe
 * Date: 2020-02-20
 * Time: 14:05
 */

class Codecia_Dynamicfilter_Model_Adminhtml_System_Config_Backend_Cron extends Mage_Core_Model_Config_Data {

    const CRON_STRING_PATH = 'crontab/jobs/codecia_dynamicfilter_cron/schedule/cron_expr';

    protected function _beforeSave() {

        $time = $this->getData('groups/configurable_cron/fields/time/value');
        $dayInterval = $this->getData('groups/configurable_cron/fields/day_interval')['value'];

        if ($dayInterval >= 0 && $dayInterval != '' && $this->getValue()) {

            $dayInterval = intval($dayInterval) ? '*/'.intval($dayInterval) : '*';

            $cronExprArray = array(
                intval($time[1]),
                intval($time[0]),
                $dayInterval,
                '*',
                '*'
            );

            $cronExprString = implode(' ', $cronExprArray);

        } else {

            $cronExprString = null;
        }

        try {

            Mage::getModel('core/config_data')
                ->load(self::CRON_STRING_PATH, 'path')
                ->setValue($cronExprString)
                ->setPath(self::CRON_STRING_PATH)
                ->save();
        }
        catch (Exception $e) {

            throw new Exception(Mage::helper('cron')->__('Unable to save the cron expression.'));
        }
    }
}