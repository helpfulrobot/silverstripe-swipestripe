<?php
/**
 * Search filter for date ranges, used in the CMS for searching {@link Order}s.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage search
 */
class DateRangeSearchFilter extends SearchFilter
{

    /**
   * Minimum date
   * 
   * @var String
   */
    protected $min;
    
    /**
   * Maximum date
   * 
   * @var String
   */
    protected $max;

    /**
     * Setter for min date value
     * 
     * @param String $min
     */
    public function setMin($min)
    {
        $this->min = $min;
    }

    /**
     * Setter for max date value
     * 
     * @param String $max
     */
    public function setMax($max)
    {
        $this->max = $max;
    }

    /**
   * Apply filter query SQL to a search query
   * Date range filtering between min and max values
   * 
   * @see SearchFilter::apply()
   */
    public function apply(SQLQuery $query)
    {
        if ($this->min && $this->max) {
            $query->where(sprintf(
            "%s >= '%s' AND %s < '%s'",
            $this->getDbName(),
            Convert::raw2sql($this->min),
            $this->getDbName(),
            Convert::raw2sql($this->max)
        ));
        }
    }
}
