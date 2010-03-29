<?php

/**
 * CodeIgniter Lastfm Library (http://www.haughin.com/code/lastfm)
 * 
 * Author: Elliot Haughin (http://www.haughin.com), elliot@haughin.com
 *
 * ========================================================
 * REQUIRES: Simplepie RSS Parser Library (http://www.haughin.com/code/simplepie)
 * ========================================================
 * 
 * Description:
 * Gets the latest played tracks for a user.
 * 
 * Get full documentation here: http://www.haughin.com/code/lastfm
 * 
 * VERSION: 1.0 (2008-02-10)
 * 
 **/

	class Lastfm
	{
		var $user;
		var $cache_time = 300;
		var $cache_path;
		
		function Lastfm()
		{
			$this->obj 	=& get_instance();
			$this->obj->load->library('simplepie');
		}
		
		function init($config)
		{
			foreach ($config as $key => $value)
			{
				$this->$key = $value;
			}
		}
		
		function get_latest()
		{
			if ( intval($this->num > 10) )
			{
				$this->num = 10;
			}
			
			$this->rss_url = 'http://ws.audioscrobbler.com/1.0/user/' . $this->user . '/recenttracks.rss';
			
			$items = $this->get_rss_items();
			
			$items = array_slice($items, 0, $this->num);
			
			$songs = array();
			
			foreach ( $items as $item )
			{
				$song['title'] = $item->get_title();
				$song['when'] = $this->relative_time($item->get_date(false));
				$songs[] = $song;
			}
			
			return $songs;
		}
		
		function relative_time($when)
		{
			$time_orig = strtotime($when);
			$diff = $just = time()-$time_orig;
		    $months = floor($diff/2592000);
		    $diff -= $months*2419200;
		    $weeks = floor($diff/604800);
		    $diff -= $weeks*604800;
		    $days = floor($diff/86400);
		    $diff -= $days*86400;
		    $hours = floor($diff/3600);
		    $diff -= $hours*3600;
		    $minutes = floor($diff/60);
		    $diff -= $minutes*60;
		    $seconds = $diff;

			$relative_date = '';

			if ($just<=0) {
				return 'Just Now!';	
			} else {
			    if ($months>0) {
			        // over a month old, just show date (yyyy/mm/dd format)
			        return 'on '.date('Y/m/d', $time_orig);
			    } else {
			        if ($weeks>0) {
			            // weeks and days
			            $relative_date .= ($relative_date?', ':'').$weeks.' week'.($weeks>1?'s':'');
			            $relative_date .= $days>0?($relative_date?', ':'').$days.' day'.($days>1?'s':''):'';
			        } elseif ($days>0) {
			            // days and hours
			            $relative_date .= ($relative_date?', ':'').$days.' day'.($days>1?'s':'');
			            $relative_date .= $hours>0?($relative_date?', ':'').$hours.' hour'.($hours>1?'s':''):'';
			        } elseif ($hours>0) {
			            // hours and minutes
			            $relative_date .= ($relative_date?', ':'').$hours.' hour'.($hours>1?'s':'');
			            $relative_date .= $minutes>0?($relative_date?', ':'').$minutes.' minute'.($minutes>1?'s':''):'';
			        } elseif ($minutes>0) {
			            // minutes only
			            $relative_date .= ($relative_date?', ':'').$minutes.' minute'.($minutes>1?'s':'');
			        } else {
			            // seconds only
			            $relative_date .= ($relative_date?', ':'').$seconds.' second'.($seconds>1?'s':'');
			        }
			    }
			}
			
		    // show relative date and add proper verbiage
		    return $relative_date.' ago';
			
		}
		
		function get_rss_items()
		{
			$this->obj->simplepie->cache_location	= $this->rss_cache_path;
			$this->obj->simplepie->cache_time		= $this->cache_time;

			$this->obj->simplepie->set_feed_url($this->rss_url);
			$this->obj->simplepie->init();

			$this->obj->simplepie->handle_content_type();

			return $this->obj->simplepie->get_items();
		}
	}

?>