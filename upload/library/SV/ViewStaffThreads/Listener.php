<?php

class SV_ViewStaffThreads_Listener
{
    const AddonNameSpace = 'SV_ViewStaffThreads_';

    public static function load_class($class, array &$extend)
    {
        $extend[] = self::AddonNameSpace.$class;
    }
}