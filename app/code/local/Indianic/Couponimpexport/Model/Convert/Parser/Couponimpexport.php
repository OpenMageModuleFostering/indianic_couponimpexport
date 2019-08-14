<?php

class Indianic_Couponimpexport_Model_Convert_Parser_Couponimpexport extends Mage_Eav_Model_Convert_Parser_Abstract
{
    const MULTI_DELIMITER = ' , ';
   
     public function unparse()
    {
           $salesRules = Mage::getModel('salesrule/rule')->getCollection();
                    $csv_fields  = array();

                     $i = 1;

           if($salesRules) { 
                foreach ($salesRules as $rules) {
                    $rule = Mage::getModel('salesrule/rule')->load($rules->getRuleId());
                                        if($rules->getCode() != ''){ 
                                             $conditionsCol = unserialize($rules->getConditionsSerialized());
                                             $conditions = $conditionsCol['conditions'];
                                             for ($i=0; $i< count($conditions); $i++){
                                                 if(isset($conditions[$i]['conditions'])){
                                                    $getProductsku = $conditions[$i]['conditions'];
                                                    for ($j=0;$j<count($getProductsku);$j++){
                                                        if($getProductsku[$j]['attribute'] == 'sku'){
                                                            $productSku = $getProductsku[$j]['value'];                                
                                                        }
                                                    }
                                                 }
                                             }
                                         }
                $csv_fields['Code'] = $rule->getCouponCode();
                $csv_fields['Customer'] = $rule->getName();
                $csv_fields['Amount'] = $rule->getDiscountAmount();
                $csv_fields['Fromdate'] = $rule->getFromDate();
                $csv_fields['Todate'] = $rule->getToDate();
                $csv_fields['Description'] = $rule->getDescription();
                $csv_fields['Sku'] = $productSku;
                
                 $batchExport = $this->getBatchExportModel()
                        ->setId(null)
                        ->setBatchId($this->getBatchModel()->getId())
                        ->setBatchData($csv_fields)
                        ->setStatus(1)
                        ->save();
               
                }
           }           
       
     return $this;
}
     public function parse()
    {
            
    }
}