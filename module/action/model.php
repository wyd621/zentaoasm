<?php
/**
 * The model file of action module of zentaoASM
 *
 * @copyright   Copyright 2009-2011 青岛易软天创网络科技有限公司 (QingDao Nature Easy Soft Network Technology Co,LTD www.cnezsoft.com)
 * @license     LGPL (http://www.gnu.org/licenses/lgpl.html)
 * @author      Congzhi Chen <congzhi@cnezsoft.com>
 * @package     action
 * @version     $Id: model.php 1956 2011-06-30 07:53:57Z wwccss $
 * @link        http://www.zentao.net
 */
?>
<?php
class actionModel extends model
{
    /**
     * Create a action.
     * 
     * @param  string $objectType 
     * @param  int    $objectID 
     * @param  string $actionType 
     * @param  string $comment 
     * @param  string $extra        the extra info of this action, according to different modules and actions, can set different extra.
     * @access public
     * @return int
     */
    public function create($objectType, $objectID, $actionType, $comment = '', $extra = '', $actor = '')
    {
        $action = new stdclass();
        $action->objectType = strtolower($objectType);
        $action->objectID   = $objectID;
        $action->actor      = $actor ? $actor : $this->app->user->account;
        $action->action     = strtolower($actionType);
        $action->date       = helper::now();
        $action->comment    = $comment;
        $action->extra      = $extra;

        $this->dao->insert(TABLE_ACTION)->data($action)->autoCheck()->exec();
        return $this->dbh->lastInsertID();
    }

    /**
     * Get actions of an object.
     * 
     * @param  int    $objectType 
     * @param  int    $objectID 
     * @access public
     * @return array
     */
    public function getList($objectType, $objectID)
    {
        $actions = $this->dao->select('*')->from(TABLE_ACTION)
            ->where('objectType')->eq($objectType)
            ->andWhere('objectID')->eq($objectID)
            ->orderBy('id')->fetchAll('id');
        $histories = $this->getHistory(array_keys($actions));
        foreach($actions as $actionID => $action)
        {
            $action->history = isset($histories[$actionID]) ? $histories[$actionID] : array();
            $actions[$actionID] = $action;
        }
        return $actions;
    }

    /**
     * Get an action record.
     * 
     * @param  int    $actionID 
     * @access public
     * @return object
     */
    public function getById($actionID)
    {
        return $this->dao->findById((int)$actionID)->from(TABLE_ACTION)->fetch();
    }

    /**
     * Print actions of an object.
     * 
     * @param  array    $action 
     * @access public
     * @return void
     */
    public function printAction($action)
    {
        $objectType = $action->objectType;
        $actionType = strtolower($action->action);

        /**
         * Set the desc string of this action.
         *
         * 1. If the module of this action has defined desc of this actionType, use it.
         * 2. If no defined in the module language, search the common action define.
         * 3. If not found in the lang->action->desc, use the $lang->action->desc->common or $lang->action->desc->extra as the default.
         */
        if(isset($this->lang->$objectType->action->$actionType))
        {
            $desc = $this->lang->$objectType->action->$actionType;
        }
        elseif(isset($this->lang->action->desc->$actionType))
        {
            $desc = $this->lang->action->desc->$actionType;
        }
        else
        {
            $desc = $action->extra ? $this->lang->action->desc->extra : $this->lang->action->desc->common;
        }
        /* Cycle actions, replace vars. */
        foreach($action as $key => $value)
        {
            /* Desc can be an array or string. */
            if(is_array($desc))
            {
                if($key == 'extra') continue;
                $desc['main'] = str_replace('$' . $key, $value, $desc['main']);
            }
            else
            {
                $desc = str_replace('$' . $key, $value, $desc);
            }
        }
        /* If the desc is an array, process extra. Please bug/lang. */
        if(is_array($desc))
        {
            $extra = strtolower($action->extra);
            if(isset($desc['extra'][$extra])) 
            {
                echo str_replace('$extra', $desc['extra'][$extra], $desc['main']);
            }
            else
            {
                echo str_replace('$extra', $action->extra, $desc['main']);
            }
        }
        else
        {
            if($action->action == 'valuated')
            {
                echo $desc . $this->lang->action->valuate . $this->lang->request->valuates[$action->extra];
            }
            else
            {
                echo $desc; 
            }
        }
    }

    /**
     * Get actions as dynamic.
     * 
     * @param  string $objectType 
     * @param  int    $count 
     * @access public
     * @return array
     */
    public function getDynamic($account = 'all', $period = 'all', $orderBy = 'id_desc', $pager = null)
    {
        $period = $this->computeBeginAndEnd($period);
        extract($period);

        $actions = $this->dao->select('*')->from(TABLE_ACTION)
            ->where('date')->gt($begin)
            ->andWhere('date')->lt($end)
            ->beginIF($account != 'all')->andWhere('actor')->eq($account)->fi()
            ->orderBy($orderBy)->page($pager)->fetchAll();

        if(!$actions) return array();

        /* Group actions by objectType, and get there name field. */
        foreach($actions as $object) $objectTypes[$object->objectType][] = $object->objectID;
        foreach($objectTypes as $objectType => $objectIds)
        {
            if(!isset($this->config->action->objectTables[$objectType])) continue;    // If no defination for this type, omit it.

            $objectIds   = array_unique($objectIds);
            $table       = $this->config->action->objectTables[$objectType];
            $field       = $this->config->action->objectNameFields[$objectType];
            if($table != 'zt_todo')
            {
                $objectNames[$objectType] = $this->dao->select("id, $field AS name")->from($table)->where('id')->in($objectIds)->fetchPairs();
            }
            else
            {
                $todos = $this->dao->select("id, $field AS name, account, private")->from($table)->where('id')->in($objectIds)->fetchAll('id');
                foreach($todos as $id => $todo)
                {
                    if($todo->private == 1 and $todo->account != $this->app->user->account) 
                    {
                       $objectNames[$objectType][$id] = $this->lang->todo->thisIsPrivate;
                    }
                    else
                    {
                       $objectNames[$objectType][$id] = $todo->name;
                    }
                }
            } 
        }
        $objectNames['user'][0] = 'guest';    // Add guest account.

        foreach($actions as $action)
        {
            /* Add name field to the actions. */
            $action->objectName = isset($objectNames[$action->objectType][$action->objectID]) ? $objectNames[$action->objectType][$action->objectID] : '';

            $actionType = strtolower($action->action);
            $objectType = strtolower($action->objectType);
            $action->date        = date(DT_MONTHTIME2, strtotime($action->date));
            $action->actionLabel = isset($this->lang->action->label->$actionType) ? $this->lang->action->label->$actionType : $action->action;
            $action->objectLabel = isset($this->lang->action->label->$objectType) ? $this->lang->action->label->$objectType : $objectType;

            /* If action type is login or logout, needn't link. */
            if($actionType == 'login' or $actionType == 'logout')
            {
                $action->objectLink  = '';
                $action->objectLabel = '';
                continue;
            }

            /* Other actions, create a link. */
            if(strpos($action->objectLabel, '|') !== false)
            {
                list($objectLabel, $moduleName, $methodName, $vars) = explode('|', $action->objectLabel);
                $action->objectLink  = helper::createLink($moduleName, $methodName, sprintf($vars, $action->objectID));
                $action->objectLabel = $objectLabel;
            }
            else
            {
                $action->objectLink = '';
            }
        }
        return $actions;
    }

    /**
     * Compute the begin date and end date of a period.
     * 
     * @param  string    $period 
     * @access private
     * @return array
     */
    private function computeBeginAndEnd($period)
    {
        $this->loadModel('todo');

        $today      = $this->todo->today();
        $tomorrow   = $this->todo->tomorrow();
        $yesterday  = $this->todo->yesterday();
        $twoDaysAgo = $this->todo->twoDaysAgo();

        if($period == 'all')        return array('begin' => '1970-1-1',  'end' => '2109-1-1');
        if($period == 'today')      return array('begin' => $today,      'end' => $tomorrow);
        if($period == 'yesterday')  return array('begin' => $yesterday,  'end' => $today);
        if($period == 'twodaysago') return array('begin' => $twoDaysAgo, 'end' => $yesterday);

        /* If the period is by week, add the end time to the end date. */
        if($period == 'thisweek' or $period == 'lastweek')
        {
            $func = "get$period";
            extract($this->todo->$func());
            return array('begin' => $begin, 'end' => $end . ' 23:59:59');
        }

        if($period == 'thismonth')  return $this->todo->getThisMonth();
        if($period == 'lastmonth')  return $this->todo->getLastMonth();
    }
}
