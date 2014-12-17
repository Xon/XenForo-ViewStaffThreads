<?php

class SV_ViewStaffThreads_Listener
{
    public static function load_class($class, array &$extend)
    {
        switch ($class)
        {
            case 'XenForo_Model_Thread':
                $extend[] = 'SV_ViewStaffThreads_XenForo_Model_Thread';
                break;
            case 'XenForo_Model_Post':
                $extend[] = 'SV_ViewStaffThreads_XenForo_Model_Post';
                break;                
        }
    }
}