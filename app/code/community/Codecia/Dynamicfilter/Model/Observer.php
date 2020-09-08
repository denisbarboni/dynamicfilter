<?php
/**
 * Created by PhpStorm.
 * User: filipe
 * Date: 2020-01-20
 * Time: 13:03
 */

class Codecia_Dynamicfilter_Model_Observer {

    public function setCategoryFilter($observer) {

        $product = $observer->getEvent()->getProduct();
        if (!in_array('952', $product->getCategoryIds())) {

            $categories = $product->getCategoryIds();
            $categories[] = '952';
            $product->setCategoryIds($categories);
        }

        return $this;
    }
}