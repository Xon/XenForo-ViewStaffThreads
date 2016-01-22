<?php

class SV_ViewStaffThreads_XenForo_Model_Thread extends XFCP_SV_ViewStaffThreads_XenForo_Model_Thread
{
    public function canViewThread(array $thread, array $forum, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
    {
        $this->standardizeViewingUserReferenceForNode($thread['node_id'], $viewingUser, $nodePermissions);
        $canViewThread = parent::canViewThread($thread, $forum, $errorPhraseKey, $nodePermissions, $viewingUser);
        if ($canViewThread)
        {
            return true;
        }

        // ensure the forum/node can actually be seen
        if (!XenForo_Permission::hasContentPermission($nodePermissions, 'view'))
        {
            return false;
        }

        if (XenForo_Permission::hasContentPermission($nodePermissions, 'viewStickies') && $thread['sticky'])
        {
            return true;
        }

        if (isset($thread['thread_user_id']))
        {
            $thread['user_id'] =  $thread['thread_user_id'];
        }
        if (isset($thread['thread_is_staff']))
        {
            $thread['is_staff'] =  $thread['thread_is_staff'];
        }

        if (XenForo_Permission::hasContentPermission($nodePermissions, 'viewStaff') && isset($thread['is_staff']) && $thread['is_staff'])
        {
            return true;
        }

        return false;
    }

    public function getPermissionBasedThreadFetchConditions(array $forum, array $nodePermissions = null, array $viewingUser = null)
    {
        $this->standardizeViewingUserReferenceForNode($forum['node_id'], $viewingUser, $nodePermissions);

        $conditions = parent::getPermissionBasedThreadFetchConditions($forum, $nodePermissions, $viewingUser);

        if (!XenForo_Permission::hasContentPermission($nodePermissions, 'viewOthers'))
        {
            if (XenForo_Permission::hasContentPermission($nodePermissions, 'viewStaff') )
            {
                $conditions['viewStaff'] = true;
            }
            if (XenForo_Permission::hasContentPermission($nodePermissions, 'viewStickies') )
            {
                $conditions['viewStickies'] = true;
            }
        }

        return $conditions;
    }

    static $widgetSupport = null;

    public function prepareThreadFetchOptions(array $fetchOptions)
    {
        // widget render support since it re-uses 'user' for the last post rather than the first post, rather than last_post_user
        if(self::$widgetSupport === null)
        {
            self::$widgetSupport = class_exists('WidgetFramework_XenForo_Model_Thread', false);
        }
        if(self::$widgetSupport && isset($fetchOptions[WidgetFramework_XenForo_Model_Thread::FETCH_OPTIONS_LAST_POST_JOIN]))
        {
            $threadFetchOptions = parent::prepareThreadFetchOptions($fetchOptions);
            $threadFetchOptions['selectFields'] .= ',
                sv_first_user.is_staff';
            $threadFetchOptions['joinTables'] .= '
                LEFT JOIN xf_user AS sv_first_user ON
                    (sv_first_user.user_id = thread.user_id)';
            return $threadFetchOptions;
        }

        if (empty($fetchOptions['join']))
        {
            $fetchOptions['join'] = XenForo_Model_Thread::FETCH_AVATAR;
        }
        else if (!($fetchOptions['join'] & XenForo_Model_Thread::FETCH_USER) && !($fetchOptions['join'] & XenForo_Model_Thread::FETCH_AVATAR))
        {
            $fetchOptions['join'] |= XenForo_Model_Thread::FETCH_AVATAR;
        }

        $threadFetchOptions = parent::prepareThreadFetchOptions($fetchOptions);

        $threadFetchOptions['selectFields'] .= ', user.is_staff';

        return $threadFetchOptions;
    }

    public function prepareThreadConditions(array $conditions, array &$fetchOptions)
    {
        $sqlConditions = array();
        $db = $this->_getDb();
        $user_id = 0;
        if (isset($conditions['user_id']))
        {
            $user_id = $conditions['user_id'];
            unset($conditions['user_id']);
        }
        $sql = parent::prepareThreadConditions($conditions, $fetchOptions);

        // thread starter
        if ($user_id)
        {
            $parts = array();
            if (isset($conditions['viewStickies']) && $conditions['viewStickies'])
            {
                $parts[] = '( thread.sticky = 1 )';
            }
            if (isset($conditions['viewStaff']) && $conditions['viewStaff'])
            {
                $parts[] = '( user.is_staff = 1 )';
            }

            $OrStatement= '';
            if ($parts)
                $OrStatement = ' OR '. implode(' OR ', $parts);

            if (is_array($user_id))
            {
                $sqlConditions[] = '( thread.user_id IN (' . $db->quote($user_id) . ')' . $OrStatement. ' )';
            }
            else
            {
                $sqlConditions[] = '( thread.user_id = ' . $db->quote($user_id) . $OrStatement. ' )';
            }
        }
        if ($sqlConditions)
        {
            $sql .= ' AND ' . $this->getConditionsForClause($sqlConditions);
        }

        return $sql;
    }
}