<?php

class SV_ViewStaffThreads_XenForo_Model_Thread extends XFCP_SV_ViewStaffThreads_XenForo_Model_Thread
{
    private $_thread_view_cache = array();


    public function canViewThread(array $thread, array $forum, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
    {
        $this->standardizeViewingUserReferenceForNode($thread['node_id'], $viewingUser, $nodePermissions);
        $canViewThread =  parent::canViewThread($thread, $forum, $errorPhraseKey, $nodePermissions, $viewingUser);
        if ($canViewThread)
            return true;

        // ensure the forum/node can actually be seen
        if (!XenForo_Permission::hasContentPermission($nodePermissions, 'view'))
        {
            return false;
        }

        if (XenForo_Permission::hasContentPermission($nodePermissions, 'viewStickies') && $thread['sticky'])
        {
            return true;
        }
        
        if (XenForo_Permission::hasContentPermission($nodePermissions, 'viewStaff') && isset($thread['is_staff']) && $thread['is_staff'])
        {
            // search results project the current post user into $thread while displaying thread results use the first post.
            if (isset($thread['thread_user_id']) && $thread['thread_user_id'] != $thread['user_id'])
            {
                // ensure we only do 1 query per thread, even if there are multipule matching posts
                if (!isset($this->_thread_view_cache[$thread['thread_id']]))                
                {       
                    $users = $this->_getDb()->fetchCol('
                                    SELECT user.is_staff 
                                    From xf_user AS user
                                    WHERE user_id = ?', $thread['thread_user_id']);
                
                    $this->_thread_view_cache[$thread['thread_id']] = isset($users) && isset($users[0]) && isset($users[0][0]) && $users[0][0] != 0; 
                }
            
                return $this->_thread_view_cache[$thread['thread_id']];
            }
        
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

    public function prepareThreadFetchOptions(array $fetchOptions)
    {
        $threadFetchOptions = parent::prepareThreadFetchOptions($fetchOptions);     
        if (isset($fetchOptions['join']))
        {            
            if ($fetchOptions['join'] & XenForo_Model_Thread::FETCH_AVATAR)
            {
                $threadFetchOptions['selectFields'] .= ', user.is_staff';
            }
        }
        else
        {
            $threadFetchOptions['selectFields'] .= ', user.is_staff';
            $threadFetchOptions['joinTables'] .= '
            LEFT JOIN xf_user AS user ON
            (user.user_id = thread.user_id)';
        }
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