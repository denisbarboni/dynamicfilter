<?php
/**
 * Created by PhpStorm.
 * User: filipe
 * Date: 2020-01-03
 * Time: 17:43
 */

class Codecia_Dynamicfilter_Model_Source_Attributes {

    public function toOptionArray(){

        $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
            ->getItems();
        $options = array(
            array('value' => 'category_id', 'label' => 'Categorias'),
            array('value' => 'subcategory_id', 'label' => 'Subcategorias')
        );
        foreach ($attributes as $attribute) {
            if (($attribute->getFrontendInput() == "select" || $attribute->getFrontendInput() == "multiselect") && $attribute->getIsFilterable() && $attribute->getIsSearchable()) {
                $options[] = array('value' => $attribute->getAttributeCode(), 'label' => $attribute->getFrontendLabel());
            }
        }

        return $options;
    }

    public function attrOptions($adminSelected){

        $isCached = $this->isCached($adminSelected);

        if (!$isCached) {

            $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
                ->getItems();
            $arrayControlFirstOption = array('', 'Selecione', 'Selecione...');
            $options = array();

            if (in_array('category_id', $adminSelected)) {
                if (Mage::getStoreConfigFlag('codecia_dynamicfilter/frontend/show_only_categories')) {
                    $configOnlyCategories = explode(',', Mage::getStoreConfig('codecia_dynamicfilter/frontend/show_only_categories'));
                }
                $categories = Mage::getModel('catalog/category')->getCollection();
                $catOptions = array();
                $subCatWasSelected = in_array('subcategory_id', $adminSelected);
                $subCategoryOptions = array();
                foreach ($categories as $category) {
                    if (isset($configOnlyCategories) && !in_array($category->getId(), $configOnlyCategories)) {
                        continue;
                    }
                    if ($category->getParentId() == 2) {
                        if($category->getIsActive()) {
                            $category = $category->load($category->getId());
                            $itemsFound = $this->categoryItems($category->getId());
                            if ($itemsFound) {
                                $catOptions[] = array('value' => $category->getId(), 'label' => $category->getName(), 'items_found' => $itemsFound, 'url_path' => $category->getUrlPath() ? $category->getUrlPath() : '');
                            }

                            if ($subCatWasSelected) {
                                $subCategories = $category->getChildrenCategories();
                                if (count($subCategories)) {
                                    foreach ($subCategories as $subCategory) {
                                        if($subCategory->getIsActive()) {
                                            $itemsFound = $this->categoryItems($subCategory->getEntityId());
                                            if ($itemsFound) {
                                                $subCategoryOptions[] = array('value' => $subCategory->getEntityId(), 'label' => $subCategory->getName(), 'items_found' => $itemsFound, 'url_path' => $subCategory->getRequestPath() ? $subCategory->getRequestPath() : '');
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                $options[] = array('id' => 'category_id', 'label' => 'Grupo', 'options' => $catOptions, 'code' => 'category_id');

                if ($subCatWasSelected) {

                    $options[] = array('id' => 'subcategory_id', 'label' => 'Categorias', 'options' => $subCategoryOptions, 'code' => 'subcategory_id');
                }
            }

            foreach ($attributes as $attribute) {
                if (in_array($attribute->getId(), $adminSelected)) {
                    $attrOptions = Mage::getSingleton('eav/config')->getAttribute(Mage_Catalog_Model_Product::ENTITY, $attribute->getAttributeCode())->getSource()->getAllOptions();
                    foreach ($arrayControlFirstOption as $compare) {
                        if ($attrOptions[0]['value'] == '') {
                            unset($attrOptions[0]);
                            break;
                        }
                        if (strtolower($compare) == strtolower($attrOptions[0]['label'])) {
                            unset($attrOptions[0]);
                        }
                    }
                    foreach ($attrOptions as $key => $value) {
                        $itemsFound = $this->verifyAttrValueHasItem($attribute->getAttributeCode(), $value['value'], $attribute->getFrontendInput());
                        if ($itemsFound) {
                            $attrOptions[$key]['items_found'] = $itemsFound;
                        } else {
                            unset($attrOptions[$key]);
                        }
                    }
                    if($attrOptions) {
                        $options[] = array('id' => $attribute->getId(), 'label' => $attribute->getFrontendLabel(), 'options' => $attrOptions, 'code' => $attribute->getAttributeCode());
                    }
                }
            }

            return $this->saveCache($options, $adminSelected);
        }

        return $isCached;
    }

    public function verifyAttrValueHasItem($attr, $value, $type = null) {

        $collection = Mage::getModel('catalog/product')->getCollection();
        $collection->addAttributeToSelect($attr);
        if ($type == 'select') {
            $collection->addFieldToFilter(array(
                array('attribute' => $attr, 'eq' => $value),
            ));
        } else if ($type == 'multiselect') {
            $collection->addFieldToFilter(array(
                array('attribute' => $attr, 'like' => '%'.$value.'%'),
            ));
        }

        if (Mage::getStoreConfigFlag('codecia_dynamicfilter/frontend/show_only_categories')) {
            $collection->joinField('category_id', 'catalog/category_product', 'category_id', 'product_id=entity_id', null, 'inner');
            $collection->addAttributeToSelect('category_id');
            $collection->addFieldToFilter(array(
                array('attribute' => 'category_id', 'in' => explode(',', Mage::getStoreConfig('codecia_dynamicfilter/frontend/show_only_categories'))),
            ));
        }

        $collection->getSelect()->columns('COUNT('.$attr.') as items_found')->group(array($attr));
        $collection->addAttributeToFilter($attr, array('notnull' => true));
        $collection->addAttributeToFilter('visibility', 4);
        $collection->addAttributeToFilter('status', array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED));
        $collection->addWebsiteFilter();
        $collection->addStoreFilter();
        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);

        if (Mage::getStoreConfigFlag('codecia_dynamicfilter/frontend/only_stock_products')) {

            Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);
        }

        if($collection->count()){
            return $collection->getFirstItem()->getData('items_found');
        }

        return false;
    }

    public function categoryItems($id) {

        $conexao = Mage::getSingleton('core/resource')->getConnection('core_write');

        $sql = "
            SELECT `cat`.`category_id`, COUNT(*) itens FROM `catalog_product_entity` AS `e`
             INNER JOIN `catalog_category_product` as `cat` ON (`e`.`entity_id` = `cat`.`product_id`)
             INNER JOIN `catalog_product_entity_int` AS `at_visibility` ON (`at_visibility`.`entity_id` = `e`.`entity_id`) AND (`at_visibility`.`attribute_id` = '102') AND (`at_visibility`.`store_id` = 0)
             INNER JOIN `catalog_product_entity_int` AS `at_status` ON (`at_status`.`entity_id` = `e`.`entity_id`) AND (`at_status`.`attribute_id` = '96') AND (`at_status`.`store_id` = 0)
        ";

        if (Mage::getStoreConfigFlag('codecia_dynamicfilter/frontend/only_stock_products')) {

            $sql .= "
                    INNER JOIN `cataloginventory_stock_item` AS `at_inventory_in_stock` ON (at_inventory_in_stock.`product_id`=e.entity_id) AND ((at_inventory_in_stock.use_config_manage_stock = 0 AND at_inventory_in_stock.manage_stock=1 AND at_inventory_in_stock.is_in_stock=1)
                    OR (at_inventory_in_stock.use_config_manage_stock = 0 AND at_inventory_in_stock.manage_stock=0) OR (at_inventory_in_stock.use_config_manage_stock = 1 AND at_inventory_in_stock.is_in_stock=1))
            ";
        }

        $sql .= "
                WHERE (at_visibility.value = '4') AND (at_status.value = 1) AND (cat.category_id = {$id})
                GROUP BY `cat`.`category_id`
        ";

        $resultado = $conexao->query($sql);

        if ($resultado->rowCount()) {
            return $resultado->fetch()['itens'];
        }

        return false;
    }

    public function saveCache($options, $adminSelected) {

        if (Mage::app()->useCache('codecia_dynamicfilter')) {

            Mage::app()->getCache()->save(serialize($options), 'Codecia_Dynamicfilter_Form_' . serialize($adminSelected), array('CODECIA_DYNAMICFILTER'));
        }

        return $options;
    }

    public function isCached($adminSelected) {

        if (($data_to_be_cached = Mage::app()->getCache()->load('Codecia_Dynamicfilter_Form_' . serialize($adminSelected)))) {

            return unserialize($data_to_be_cached);
        }

        return false;
    }
}