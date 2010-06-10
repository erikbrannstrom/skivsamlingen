<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Years Since
 *
 * Returns the number of whole years since a given date-
 *
 * @access	public
 * @param	integer	day
 * @param	integer	month
 * @param	integer	year
 * @return	integer
 */
if ( ! function_exists('years_since'))
{
	function years_since($day, $month = null, $year = null)
	{
        if($month == null) {
            $date = date('Y-m-d', strtotime($day));
            list($year, $month, $day) = explode('-', $date);
        }
		$year_diff  = date("Y") - $year;
		$month_diff = date("m") - $month;
		$day_diff   = date("d") - $day;
		if ($day_diff < 0 || $month_diff < 0)
			$year_diff--;
		return $year_diff;
	}
}