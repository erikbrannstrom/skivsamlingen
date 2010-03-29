<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?>

Array
(
    [0] => Array
        (
            [type] => query
            [query] => SELECT n.id AS n__id, n.title AS n__title, n.posted AS n__posted, n.body AS n__body FROM news n ORDER BY n.posted desc LIMIT 1
            [time] => 0.000361
        )

    [1] => Array
        (
            [type] => query
            [query] => SELECT COUNT(*) AS u__0 FROM users u GROUP BY u.id
            [time] => 0.000636
        )

)

Total Doctrine time: 0.000997066497803
Peak Memory: 7371880