<?php
/**
 * Search filter for determining whether an {@link Order} has a {@link Payment} attached.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage search
 */
class PaymentSearchFilter extends SearchFilter
{

    /**
   * Apply filter query SQL to a search query
   * 
   * @see SearchFilter::apply()
   */
    public function apply(SQLQuery $query)
    {
        $query = $this->applyRelation($query);
        $value = $this->getValue();

        if ($value == 0 || $value == 1) {
            $query->leftJoin(
                $table = "Payment", // framework already applies quotes to table names here!
                $onPredicate = "\"Payment\".\"OrderID\" = \"Order\".\"ID\"",
                $tableAlias = 'Payment'
            );
            
            if ($value == 0) {
                $query->where('"Payment"."ID" IS NULL');
            }
            if ($value == 1) {
                $query->where('"Payment"."ID" IS NOT NULL');
            }
        }
        return $query;
    }

    /**
     * Determine whether the filter should be applied, depending on the 
     * value of the field being passed
     * 
     * @see SearchFilter::isEmpty()
     * @return Boolean
     */
    public function isEmpty()
    {
        return $this->getValue() == null || $this->getValue() == ''; //|| $this->getValue() == 0;
    }
}
