<?php

class SV_ViewStaffThreads_XenForo_Model_Post extends XFCP_SV_ViewStaffThreads_XenForo_Model_Post
{

    public function preparePostJoinOptions(array $fetchOptions)
    {
        $postFetchOptions = parent::preparePostJoinOptions($fetchOptions);
        if (!empty($fetchOptions['join']))
        {
            if ($fetchOptions['join'] & XenForo_Model_Post::FETCH_THREAD || $fetchOptions['join'] & XenForo_Model_Post::FETCH_FORUM || $fetchOptions['join'] & XenForo_Model_Post::FETCH_NODE_PERMS)
            {
                $postFetchOptions['selectFields'] .= ',
                    COALESCE(first_post_user.is_staff,0) as thread_is_staff ';
                $postFetchOptions['joinTables'] .= '
                    LEFT JOIN xf_user AS first_post_user ON
                        (first_post_user.user_id = thread.user_id)';
            }
        }

        return $postFetchOptions;
    }
}