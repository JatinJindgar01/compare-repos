<?php 
    
    /**
     *@author: Jessy James
    */
    require_once 'models/BasicEnum.php';

    abstract class OrderDeliverStatuses extends BasicEnum {
        const PLACED = 'PLACED';
        const PROCESSED = 'PROCESSED';
        const SHIPPED = 'SHIPPED';
        const DELIVERED = 'DELIVERED';
        const RETURNED = 'RETURNED';
    }

