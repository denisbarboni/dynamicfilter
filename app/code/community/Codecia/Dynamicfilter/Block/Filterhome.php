<?php

class Codecia_Dynamicfilter_Block_Filterhome extends Mage_Core_Block_Template {

    protected $_template = 'codecia_dynamicfilter/filter_home.phtml';

    public function getHtmlOptions($code_attribute){

        if ($code_attribute == 'category_id'){

            $html = $this->getHtmlCachedByAttribute($code_attribute);
            if (!$html){
                $html = '';

                if (!Mage::getStoreConfigFlag('codecia_dynamicfilter/frontend/show_only_categories')) {
                    return '';
                }

                $configOnlyCategories = explode(',', Mage::getStoreConfig('codecia_dynamicfilter/frontend/show_only_categories'));
                if ($configOnlyCategories && is_array($configOnlyCategories)){
                    /** @var Mage_Catalog_Model_Resource_Category_Collection $categories */
                    $categories = Mage::getModel('catalog/category')->getCollection();
                    $categories->addAttributeToSelect('name');
                    $categories->addAttributeToFilter('entity_id', array('in', $configOnlyCategories));
                    if ($categories->count()){
                        $html .= '<div class="cc-select-border">';
                        $html .= '<label for="select-filter-category-id">Grupo</label>';
                        $html .= '<select class="select_dynamicfilter" name="select-filter-category-id'.'" id="select_filter_category_id">';
                        $html .= '<option value="" selected></option>';
                        foreach ($categories->getItems() as $category) {
                            /** @var Mage_Catalog_Model_Category $category */
                            if ($category && $category->getId()){
                                if (Mage::getStoreConfigFlag('codecia_dynamicfilter/frontend/addQty')):
                                    $html .= '<option data-url="'.$category->getRequestPath().'" value="' . $category->getId(). '">' . $category->getName() . ' (' . $category->getProductCount() . ')</option>';
                                else:
                                    $html .= '<option data-url="'.$category->getRequestPath().'" value="' . $category->getId() . '">' . $category->getName() . '</option>';
                                endif;
                            }
                        }
                        $html .= '</select>';
                        $html .= '</div>';
                    }
                }

                $this->saveAttrCache($html, $code_attribute);
            }
            return $html;
        }

        if($code_attribute == 'subcategory_id'){
            $html = $this->getHtmlCachedByAttribute($code_attribute);
            if (!$html){

                $html = '';

                if (!Mage::getStoreConfigFlag('codecia_dynamicfilter/frontend/show_only_categories')) {
                    return '';
                }

                $configOnlyCategories = explode(',', Mage::getStoreConfig('codecia_dynamicfilter/frontend/show_only_categories'));

                /** @var Mage_Catalog_Model_Resource_Category_Collection $categories */
                $categories = Mage::getModel('catalog/category')->getCollection();
                $categories->addAttributeToSelect('name');
                $categories->addAttributeToFilter('entity_id', array('in', $configOnlyCategories));
                if ($categories->count()){
                    $html .= '<div class="cc-select-border">';
                        $html .= '<label for="select-filter-category-id">Categoria</label>';
                        $html .= '<select class="select_dynamicfilter" name="select-filter-sub-category-id'.'" id="select_filter_sub_category_id">';
                            $html .= '<option value="" selected></option>';
                            foreach ($categories->getItems() as $category) {
                                /** @var Mage_Catalog_Model_Category $category */
                                foreach ($category->getChildrenCategories() as $child){
                                    if ($child && $child->getId()){
                                        $html .= '<option data-url="'.$child->getRequestPath().'" value="'.$child->getId().'">'.$child->getName().'</option>';
                                    }
                                }
                            }
                        $html .= '</select>';
                    $html .= '</div>';
                }
                $this->saveAttrCache($html, $code_attribute);
            }

            return $html;
        }

        $html = $this->getHtmlCachedByAttribute($code_attribute);
        if (!$html){
            $html = '';
            if ($code_attribute):
                $attribute = Mage::getSingleton('eav/config')->getAttribute('catalog_product', $code_attribute);
                if ($attribute->usesSource()):
                    $options = $attribute->getSource()->getAllOptions(false);
                    $optionsDelete = array();
                    if ($options):
                        $html .= '<div class="cc-select-border">';
                        $html .= '<label for="select-filter-'.$attribute->getAttributeCode().'">'.$attribute->getFrontendLabel().'</label>';
                        $html .= '<select class="select_dynamicfilter" data-name="'.$attribute->getFrontendLabel().'" data-attr="'.$attribute->getAttributeCode().'" name="select-filter-'.$attribute->getAttributeCode().'" id="select_filter_'.$attribute->getAttributeCode().'">';
                        $html .= '<option value="" selected></option>';
                        foreach ($options as $key => $option):
                            $html .= '<option value="'.$option['value'].'"';
                            if (isset($option['url_path'])):
                                $html .= 'data-url="'.$option['url_path'].'"';
                            endif;
                            $html .= '>';
                            $html .= $option['label'];
                            $html .= '</option>';
                        endforeach;
                        $html .= '</select>';
                        $html .= '</div>';
                    endif;
                endif;
            endif;
            $this->saveAttrCache($html, $code_attribute);

        }
        return $html;
    }

    public function saveAttrCache($html, $code_attr) {
        if (Mage::app()->useCache('codecia_dynamicfilter')) {
            Mage::app()->getCache()->save($html, 'codecia_dynamicfilter_html_'.$code_attr, array('CODECIA_DYNAMICFILTER'));
        }
        return $this;
    }

    public function getHtmlCachedByAttribute($code_attr) {

        if (!$code_attr){
            return '';
        }

        $id = 'codecia_dynamicfilter_html_'.$code_attr;

        $htmlCache = Mage::app()->getCache()->load($id);
        if ($htmlCache){
            return $htmlCache;
        }

        return '';
    }

}