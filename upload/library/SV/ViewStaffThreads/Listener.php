<?php

class SV_ViewStaffThreads_Listener
{
    const AddonNameSpace = 'SV_ViewStaffThreads';

    public static function load_class($class, array &$extend)
    {
        $extend[] = self::AddonNameSpace.'_'.$class;
    }
}