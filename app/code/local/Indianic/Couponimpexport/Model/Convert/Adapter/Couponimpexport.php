<?php
class Indianic_Couponimpexport_Model_Convert_Adapter_Couponimpexport extends Mage_Catalog_Model_Convert_Adapter_Product

{
    public function saveRow( array $data )
    {
                    $code = $data['coupon'];
                    $customer= $data['customer'];
                    $amt = $data['amount'];
                   
                    if($data['fromdate']){
                       $fromDate =  strtotime($data['fromdate']); 
                    } else {
                        $fromdate = date('d-m-Y');
                    }
                    
                   $toDate =  strtotime($data['todate']);    
                   $description = $data['description'];
                   $skuStr =  $data['sku']; 

                    $deleteId = $this->isExistcode($code);
                    
                    if($code != '' && $amt != ''){    
                        if($deleteId) {
                            $this->deleteRule($deleteId); //delete existing coupon code
                            $this->generateRule($code,$customer,$amt,$fromDate,$toDate,$description,$sortOrder,$skuStr);
                        } else {
                           $this->generateRule($code,$customer,$amt,$fromDate,$toDate,$description,$sortOrder,$skuStr);
                        }
                    
                   }
                    
            
    }
    
    public function isExistcode($singlecode)
        {
            $allCode = $rule = Mage::getModel('salesrule/rule')->getCollection();
            $codes = array();
            foreach ($allCode as $row) {
                $codes[$row->rule_id] = $row->code;
            }
                return array_search($singlecode, $codes); 
        }
        
      public function deleteRule($id) {
            $rule = Mage::getModel('salesrule/rule');
            $rule->load($id);
            $rule->delete();
        }
        
     public function generateRule($code,$customer_name,$disAmt,$fromDate,$toDate,$description,$sortOrder,$skuStr){
          
            $rule = Mage::getModel('salesrule/rule');
            $rule->setName($customer_name);
            $rule->setDescription($description);
            if($fromDate) {
                 $rule->setFromDate($fromDate);//starting today
            } else {
                 $rule->setFromDate(date('d-m-Y'));//starting today
            }
            
            if($toDate != '') {
                $rule->setToDate($toDate);//if you need an expiration date
            }
            $rule->setCouponType(2); //specific counpon
            $rule->setCouponCode($code);
            $rule->setUsesPerCoupon(1);//number of allowed uses for this coupon
            $rule->setUsesPerCustomer(1);//number of allowed uses for this coupon for each customer
            $rule->setCustomerGroupIds($this->getAllCustomerGroups());//if you want only certain groups replace getAllCustomerGroups() with an array of desired ids
            $rule->setIsActive(1);
            $rule->setStopRulesProcessing(0);//set to 1 if you want all other rules after this to not be processed
            $rule->setIsRss(1);//set to 1 if you want this rule to be public in rss
            $rule->setIsAdvanced(1);
            
            $rule->setProductIds('');
            
            if($sortOrder) {
               $rule->setSortOrder($sortOrder);// order in which the rules will be applied 
            }  else {
                $rule->setSortOrder(0);// order in which the rules will be applied
            }
            
        
            $rule->setSimpleAction('by_percent');
            //all available discount types
            //by_percent - Percent of product price discount
            //by_fixed - Fixed amount discount
            //cart_fixed - Fixed amount discount for whole cart
            //buy_x_get_y - Buy X get Y free (discount amount is Y)
        
            $rule->setDiscountAmount($disAmt);//the discount amount/percent. if SimpleAction is by_percent this value must be <= 100
            $rule->setDiscountQty(0);//Maximum Qty Discount is Applied to
            $rule->setDiscountStep(0);//used for buy_x_get_y; This is X
            $rule->setSimpleFreeShipping(0);//set to 1 for Free shipping
            $rule->setApplyToShipping(1);//set to 0 if you don't want the rule to be applied to shipping
            $rule->setWebsiteIds($this->getAllWbsites());//if you want only certain websites replace getAllWbsites() with an array of desired ids
        
            $conditions = array();
            $conditions[1] = array(
            'type' => 'salesrule/rule_condition_combine',
            'aggregator' => 'all',
            'value' => "1", //[UPDATE] added quotes on the value(If ALL  of these conditions are TRUE) set 0 for FALSE.
            'new_child' => ''
            );
            //the conditions above are for 'if all of these conditions are true'
            //for if any one of the conditions is true set 'aggregator' to 'any'
            //for if all of the conditions are false set 'value' to 0.
            //for if any one of the conditions is false set 'aggregator' to 'any' and 'value' to 0
            /*$conditions['1--1'] = Array
            (
            'type' => 'salesrule/rule_condition_address',
            'attribute' => 'base_subtotal',
            'operator' => '>=',
            'value' => 200
            ); */
        
            //the constraints above are for 'Subtotal is equal or grater than 200'
            //for 'equal or less than' set 'operator' to '<='... You get the idea other operators for numbers: '==', '!=', '>', '<'
            //for 'is one of' set operator to '()';
            //for 'is not one of' set operator to '!()';
            //in this example the constraint is on the subtotal
            //for other attributes you can change the value for 'attribute' to: 'total_qty', 'weight', 'payment_method', 'shipping_method', 'postcode', 'region', 'region_id', 'country_id'
        
            //to add an other constraint on product attributes (not cart attributes like above) uncomment and change the following:
              if($skuStr){
                  
                $conditions['1--2'] = array
                (
                'type' => 'salesrule/rule_condition_product_found',//-> means 'if all of the following are true' - same rules as above for 'aggregator' and 'value'
                //other values for type: 'salesrule/rule_condition_product_subselect' 'salesrule/rule_condition_combine'
                'value' => 1, //set 0 for not Found,1 is for Found
                'aggregator' => 'all',
                'new_child' => '',
                );
           
              
                $conditions['1--2--1'] = array
                  (
                  'type' => 'salesrule/rule_condition_product',
                  'attribute' => 'sku',
                  'operator' => '()',
                  'value' => $skuStr,
                  );
               
            }
            
            //$conditions['1--2--1'] means sku equals 12. For other constraints change 'attribute', 'operator'(see list above), 'value'
        
            $rule->setData('conditions',$conditions);
            $rule->loadPost($rule->getData());
            $rule->save();
        
            //[UPDATE]if you work with Mangento EE and you want to link banners to your rule uncomment the line of code below
            //Mage::getResourceModel('enterprise_banner/banner')->bindBannersToSalesRule($rule->getId(), array(1,2));//the array(1,2, ...) is the array with all the banners you want to link to the rule.
            //[/UPDATE]
        }
        
         public function getAllCustomerGroups(){
            //get all customer groups
            $customerGroups = Mage::getModel('customer/group')->getCollection();
            $groups = array();
            foreach ($customerGroups as $group){
                $groups[] = $group->getId();
            }
            return $groups;
        }
        
        
       public function getAllWbsites(){
            //get all wabsites
            $websites = Mage::getModel('core/website')->getCollection();
            $websiteIds = array();
            foreach ($websites as $website){
                $websiteIds[] = $website->getId();
            }
            return $websiteIds;
        } 
   
}

