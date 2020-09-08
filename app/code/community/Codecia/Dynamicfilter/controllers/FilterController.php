<?php

class Codecia_Dynamicfilter_FilterController extends Mage_Core_Controller_Front_Action
{

    protected $_selectedFilters = null;
    protected $_filtersToFilter = null;

    public function connectfiltersAction()
    {

        $this->_selectedFilters = $this->getRequest()->getPost('selectedFilters');
        $this->_filtersToFilter = $this->getRequest()->getPost('filters');

        $isCached = $this->isCached();

        if (!$isCached) {

            $response = array(
                'status' => false,
                'message' => 'atributo inválido',
                'filtersWithResult' => array(),
                'filtersWithoutResult' => array()
            );

            if (in_array('category_id', $this->_filtersToFilter)) {

                unset($this->_filtersToFilter[array_search('category_id', $this->_filtersToFilter)]);
                $this->categoryFilter('category_id', $response);
            }

            if (in_array('subcategory_id', $this->_filtersToFilter)) {

                unset($this->_filtersToFilter[array_search('subcategory_id', $this->_filtersToFilter)]);
                $this->categoryFilter('subcategory_id', $response);
            }

            foreach ($this->_filtersToFilter as $FTF) {
                /** @var Mage_Catalog_Model_Resource_Product_Collection $collection */
                $collection = Mage::getModel('catalog/product')->getCollection();
                $collection->addAttributeToSelect($FTF);
                if (count($this->_selectedFilters)) {

                    $hasSubcategory = false;
                    foreach ($this->_selectedFilters as $selectedFilter) {
                        if ($selectedFilter[0] == 'subcategory_id') {
                            $hasSubcategory = true;
                        }
                    }

                    foreach ($this->_selectedFilters as $SF) {

                        if ($hasSubcategory && $SF[0] == 'category_id') continue;

                        if (isset($SF[1]) && $SF[1] != '' && !is_null($SF[1])) {

                            if ($SF[0] == 'subcategory_id') {
                                $SF[0] = 'category_id';
                            }

                            if ($SF[0] == 'category_id') {
                                $collection->joinField('category_id', 'catalog/category_product', 'category_id', 'product_id=entity_id', null, 'inner');
                            }

                            $collection->addAttributeToSelect($SF[0]);
                            $collection->addFieldToFilter(array(
                                array('attribute' => $SF[0], 'eq' => $SF[1]),
                            ));
                        }
                    }
                }

                if ($FTF != 'modeloaplicacoes') {
                    $collection->getSelect()->columns('COUNT(' . $FTF . ') as items_found')->group(array($FTF));
                } else {
                    $collection->getSelect();
                }
                $collection->addAttributeToFilter($FTF, array('notnull' => true));
                $collection->addAttributeToFilter('visibility', 4);
                $collection->addAttributeToFilter('status', array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED));
                $collection->setOrder($FTF, 'ASC');
                $collection->addWebsiteFilter();
                $collection->addStoreFilter();
                Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);

                if (Mage::getStoreConfigFlag('codecia_dynamicfilter/frontend/only_stock_products')) {

                    Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);
                }

                if ($collection->count()) {
                    $response['status'] = true;
                    $response['message'] = 'Valores encontrados';

                    $attributeModel = Mage::getModel('eav/entity_attribute')->loadByCode('catalog_product', $FTF);
                    $typeAttr = $attributeModel->getFrontendInput();
                    if ($typeAttr == 'select') {
                        foreach ($collection as $product) {
                            if ($product->getData($FTF . '_value') != '' && !is_null($product->getData($FTF . '_value'))) {
                                $response['filtersWithResult'][$FTF][] = array(
                                    'label' => $product->getData($FTF . '_value'),
                                    'value' => $product->getData($FTF),
                                    'items_found' => Mage::getStoreConfigFlag('codecia_dynamicfilter/frontend/addQty') ? $product->getData('items_found') : false
                                );
                            }
                        }
                    } else if ($typeAttr == 'multiselect') {
                        $options = Mage::getSingleton('eav/config')->getAttribute(Mage_Catalog_Model_Product::ENTITY, $FTF)->getSource()->getAllOptions();
                        $values = '';
                        foreach ($collection as $product) {
                            $values .= $product->getData($FTF) . ',';
                        }
                        $values = explode(',', $values);
                        foreach ($options as $option) {
                            if ($option['value'] != '') {
                                if (in_array($option['value'], $values)) {
                                    $response['filtersWithResult'][$FTF][] = array(
                                        'label' => $option['label'],
                                        'value' => $option['value'],
                                        'items_found' => Mage::getStoreConfigFlag('codecia_dynamicfilter/frontend/addQty') ? array_count_values($values)[$option['value']] : false
                                    );
                                }
                            }
                        }
                    }
                } else {
                    $response['filtersWithoutResult'][] = $FTF;
                }
            }

            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($this->saveCache($response)));

        } else {

            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($isCached));
        }
    }

    public function categoryFilter($code, &$response)
    {

        $sql = "
                    SELECT `cat`.`category_id`, `cat_name`.`value` as category_name, COUNT(*) as itens FROM `catalog_product_entity` AS `e`
                     INNER JOIN `catalog_category_product` as `cat` ON (`e`.`entity_id` = `cat`.`product_id`)
                     INNER JOIN `catalog_category_entity` as `cat_entity` ON (`cat_entity`.`entity_id` = `cat`.`category_id`)
                     INNER JOIN `catalog_category_entity_varchar` as `cat_name` ON (`cat_name`.`entity_id` = `cat`.`category_id`)
                     INNER JOIN `catalog_product_flat_1` as `prod` ON (`e`.`entity_id` = `prod`.`entity_id`)
                     INNER JOIN `catalog_product_entity_int` AS `at_visibility` ON (`at_visibility`.`entity_id` = `e`.`entity_id`) AND (`at_visibility`.`attribute_id` = '102') AND (`at_visibility`.`store_id` = 0)
                     INNER JOIN `catalog_product_entity_int` AS `at_status` ON (`at_status`.`entity_id` = `e`.`entity_id`) AND (`at_status`.`attribute_id` = '96') AND (`at_status`.`store_id` = 0)
                ";

        if (Mage::getStoreConfigFlag('codecia_dynamicfilter/frontend/only_stock_products')) {

            $sql .= "
                        INNER JOIN `cataloginventory_stock_item` AS `at_inventory_in_stock` ON (at_inventory_in_stock.`product_id`=e.entity_id)
                        AND ((at_inventory_in_stock.use_config_manage_stock = 0 AND at_inventory_in_stock.manage_stock=1 AND at_inventory_in_stock.is_in_stock=1)
                        OR (at_inventory_in_stock.use_config_manage_stock = 0 AND at_inventory_in_stock.manage_stock=0) OR (at_inventory_in_stock.use_config_manage_stock = 1 AND at_inventory_in_stock.is_in_stock=1))
                    ";
        }

        $sql .= " WHERE (at_visibility.value = '4') AND (at_status.value = 1) ";

        if (count($this->_selectedFilters)) {

            foreach ($this->_selectedFilters as $filter) {

                if ($filter[0] == 'category_id' || $filter[0] == 'subcategory_id') continue;
                $sql .= " AND (prod." . $filter[0] . " = '" . $filter[1] . "') ";
            }
        }

        $sql .= " AND (cat_name.attribute_id = '41') ";

        if ($code == 'category_id') {
            $subCategory = false;
            foreach ($this->_selectedFilters as $selectedFilter) {
                if ($selectedFilter[0] == 'subcategory_id') {
                    $subCategory = $selectedFilter[1];
                }
            }
            if ($subCategory) {
                $categoryParent = $this->getSubCategoryParent($subCategory);
                $sql .= " AND (cat.category_id = '{$categoryParent}') ";
            } else {
                $sql .= " AND (cat_entity.parent_id = '2') ";
            }
        } else {
            $categoryId = ' > 2';
            foreach ($this->_selectedFilters as $selectedFilter) {
                if ($selectedFilter[0] == 'category_id') {
                    $categoryId = ' = ' . $selectedFilter[1];
                }
            }
            $sql .= " AND (cat_entity.parent_id {$categoryId}) ";
        }

        $sql .= " GROUP BY `cat`.`category_id` ";

        $conexao = Mage::getSingleton('core/resource')->getConnection('core_write');

        $resultado = $conexao->query($sql);

        if ($resultado->rowCount()) {

            $response['status'] = true;
            $response['message'] = 'Valores encontrados';
            while ($row = $resultado->fetch()) {

                if ($row['itens']) {

                    $response['filtersWithResult'][$code][] = array(
                        'label' => $row['category_name'],
                        'value' => $row['category_id'],
                        'data_url' => Mage::getModel('catalog/category')->load($row['category_id'])->getUrlPath(),
                        'items_found' => Mage::getStoreConfigFlag('codecia_dynamicfilter/frontend/addQty') ? $row['itens'] : false
                    );
                }
            }
        }
    }

    public function getSubCategoryParent($subCategoryId)
    {

        $parentCategories = Mage::getModel('catalog/category')->getCollection();
        foreach ($parentCategories as $category) {
            if ($category->getParentId() == 2) {
                if ($category->getIsActive()) {
                    if (isset($category->getChildrenCategories()[$subCategoryId])) {
                        return $category->getEntityId();
                    }
                }
            }
        }
    }

    public function saveCache($response)
    {

        if (Mage::app()->useCache('codecia_dynamicfilter')) {

            Mage::app()->getCache()->save(serialize($response), 'Codecia_Dynamicfilter_Filter_' . serialize(array_merge($this->_selectedFilters, $this->_filtersToFilter)), array('CODECIA_DYNAMICFILTER'));
        }

        return $response;
    }

    public function isCached()
    {

        if (!is_null($this->_selectedFilters)) {

            if (($data_to_be_cached = Mage::app()->getCache()->load('Codecia_Dynamicfilter_Filter_' . serialize(array_merge($this->_selectedFilters, $this->_filtersToFilter))))) {

                return unserialize($data_to_be_cached);
            }
        }

        return false;
    }


    public function filterattributesAction()
    {

        $response = array(
            'qtd_produtos' => 0,
            'ids' => array(),
        );

        if ($this->getRequest()->isPost()) {
            $postData = $this->getRequest()->getPost();
            if (isset($postData['values'])) {
                $values = json_decode($postData['values'], true);
                if ($values && is_array($values)) {

                    $attr_codes_with_results = array();
                    $attr_codes_without_results = array();

                    foreach ($values as $key => $value) {

                        $code = explode('_', $key)[2];
                        if (!$value) {
                            $attr_codes_without_results[$code] = $value;
                            continue;
                        }

                        $attr_codes_with_results[$code] = $value;
                    }

                    if (count($attr_codes_with_results)) {

                        /** @var Mage_Catalog_Model_Resource_Product_Collection $collection */
                        $collection = Mage::getModel('catalog/product')->getCollection();
                        $collection->addAttributeToSelect('*');
                        foreach ($attr_codes_with_results as $attr_with_code => $attr_with_value) {
                            if ($attr_with_code == 'category') continue;
                            $collection->addAttributeToFilter($attr_with_code, array('eq' => $attr_with_value));
                        }

                        if (!array_key_exists('category', $attr_codes_with_results)) {
                            $configOnlyCategories = explode(',', Mage::getStoreConfig('codecia_dynamicfilter/frontend/show_only_categories'));
                            if ($configOnlyCategories && count($configOnlyCategories) && is_array($configOnlyCategories)) {
                                $collection->addAttributeToFilter('category_id', array('in' => $configOnlyCategories));
                            }
                        }else{
                            $category_id = $attr_codes_with_results['category'];
                            if ($category_id){
                                /** @var Mage_Catalog_Model_Category $modelCategory */
                                $modelCategory = Mage::getModel('catalog/category')->load($category_id);
                                if ($modelCategory && $modelCategory->getId()){
                                    $collection->addCategoryFilter($modelCategory);
                                }
                            }
                        }

                        if ($collection->count()) {
                            $response['qtd_produtos'] = $collection->count();

                            $attrData = array();

                            foreach ($collection->getItems() as $product) {
                                $response['ids'][] = $product->getSku();

                                if (count($attr_codes_without_results)) {

                                    foreach ($attr_codes_without_results as $attr_without_code => $attr_without_value) {
                                        if ($attr_without_code == 'category_id' || $attr_without_code == 'sub') continue;
                                        $attribute = Mage::getSingleton('eav/config')->getAttribute('catalog_product', $attr_without_code);
                                        $options = array();
                                        if ($attribute->usesSource()) {
                                            $options = $attribute->getSource()->getAllOptions(false);
                                        }
                                        if (count($options)) {
                                            foreach ($options as $opt) {
                                                if ($opt['value'] == $product->getData($attr_without_code)) {
                                                    if (!isset($attrData[$attr_without_code][$opt['label']])) {
                                                        $attrData[$attr_without_code][$opt['label']]['value'] = $opt['value'];
                                                        $attrData[$attr_without_code][$opt['label']]['label'] = $opt['label'];
                                                        $attrData[$attr_without_code][$opt['label']]['count'] = 1;
                                                        continue;
                                                    } else {
                                                        $attrData[$attr_without_code][$opt['label']]['count'] = ($attrData[$attr_without_code]['count'] + 1);
                                                        continue;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            if (count($attrData)) {

                                foreach ($attrData as $attr_code => $values) {

                                    //pegar os atributos que não tiveram resultados( Ou seja, nenhum produto da collection tem esses atributos).
                                    if (array_key_exists($attr_code, $attr_codes_without_results)) {
                                        unset($attr_codes_without_results[$attr_code]);
                                    }

                                    foreach ($values as $label => $value) {
                                        $html_attribute = '';
                                        if (Mage::getStoreConfigFlag('codecia_dynamicfilter/frontend/addQty')):
                                            $html_attribute .= '<option value="' . $value['value'] . '">' . $value['label'] . ' (' . $value['count'] . ')</option>';
                                        else:
                                            $html_attribute .= '<option value="' . $value['value'] . '">' . $value['label'] . '</option>';
                                        endif;

                                        if (!isset($response['select_filter'][$attr_code])) {
                                            $optionDefault = '<option value=""></option>';
                                            $response['select_filter'][$attr_code][] = $optionDefault . $html_attribute;
                                        } else {
                                            $response['select_filter'][$attr_code][] = $html_attribute;
                                        }
                                    }
                                }

                                $response['filter_without_result'] = $attr_codes_without_results;
                            }
                        }
                    }
                }
            }
        }

        return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
    }

    public function filtersubcategoriesAction(){

        $response = array(
            'html' => ''
        );

        if ($this->getRequest()->isPost()) {
            $postData = $this->getRequest()->getPost();
            if (isset($postData['category_id'])) {
                $category_id = $postData['category_id'];
                if ($category_id){
                    /** @var Mage_Catalog_Model_Resource_Category $category */
                    $category = Mage::getModel('catalog/category')->load($category_id);
                    if ($category->getChildrenCount()){
                        $html_attribute = '';
                        $addQty = Mage::getStoreConfigFlag('codecia_dynamicfilter/frontend/addQty');
                        /** @var Mage_Catalog_Model_Resource_Category $child_category */
                        foreach ($category->getChildrenCategories($category) as $child_category){
                            if ($addQty):
                                $html_attribute .= '<option data-url="'.$child_category->getRequestPath().'" value="' . $child_category->getId(). '">' . $child_category->getName() . ' (' . $child_category->getProductCount() . ')</option>';
                            else:
                                $html_attribute .= '<option data-url="'.$child_category->getRequestPath().'" value="' . $child_category->getId() . '">' . $child_category->getName() . '</option>';
                            endif;
                        }
                    }
                }else{
                    if (!Mage::getStoreConfigFlag('codecia_dynamicfilter/frontend/show_only_categories')) {
                        return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
                    }

                    $configOnlyCategories = explode(',', Mage::getStoreConfig('codecia_dynamicfilter/frontend/show_only_categories'));
                    if ($configOnlyCategories && is_array($configOnlyCategories)){
                        /** @var Mage_Catalog_Model_Resource_Category_Collection $categories */
                        $categories = Mage::getModel('catalog/category')->getCollection();
                        $categories->addAttributeToSelect('name');
                        $categories->addAttributeToFilter('entity_id', array('in', $configOnlyCategories));
                        if ($categories->count()){
                            $html_attribute = '';
                            foreach ($categories as $child_category){
                                if (Mage::getStoreConfigFlag('codecia_dynamicfilter/frontend/addQty')):
                                    $html_attribute .= '<option data-url="'.$child_category->getRequestPath().'" value="' . $child_category->getId(). '">' . $child_category->getName() . ' (' . $child_category->getProductCount() . ')</option>';
                                else:
                                    $html_attribute .= '<option data-url="'.$child_category->getRequestPath().'" value="' . $child_category->getId() . '">' . $child_category->getName() . '</option>';
                                endif;
                            }
                        }
                    }
                }
            }
        }

        $response['html'] = $html_attribute;

        return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
    }
}
