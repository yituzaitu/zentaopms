<?php
/**
 * The model file of ci module of ZenTaoPMS.
 * @author      Chenqi <chenqi@cnezsoft.com>
 * @package     product
 * @version     $Id: $
 * @link        http://www.zentao.net
 */

class ciModel extends model
{
    /**
     * Set menu.
     * 
     * @access public
     * @return void
     */
    public function setMenu()
    {
        $repoID = $this->session->repoID;
        $moduleName = $this->app->getModuleName();
        foreach($this->lang->{$moduleName}->menu as $key => $menu) common::setMenuVars($this->lang->{$moduleName}->menu, $key, $repoID);
        $this->lang->{$moduleName}->menuOrder = $this->lang->ci->menuOrder;
    }

    /**
     * Send a request to jenkins to check build status.
     *
     * @access public
     * @return void
     */
    public function checkBuildStatus()
    {
        $compiles = $this->dao->select('t1.*, t2.jkJob, t3.name as jenkinsName,t3.url,t3.account,t3.token,t3.password')
            ->from(TABLE_COMPILE)->alias('t1')
            ->leftJoin(TABLE_JOB)->alias('t2')->on('t1.job=t2.id')
            ->leftJoin(TABLE_JENKINS)->alias('t3')->on('t2.jkHost=t3.id')
            ->where('t1.status')->ne('success')
            ->andWhere('t1.status')->ne('fail')
            ->andWhere('t1.status')->ne('create_fail')
            ->andWhere('t1.status')->ne('timeout')
            ->andWhere('t1.createdDate')->gt(date(DT_DATETIME1, strtotime("-1 day")))
            ->fetchAll();

        foreach($compiles as $compile) $this->checkStatus($compile);
    }

    /**
     * Check status.
     * 
     * @param  object $compile 
     * @access public
     * @return void
     */
    public function checkStatus($compile)
    {
        $jenkinsServer   = $compile->url;
        $jenkinsUser     = $compile->account;
        $jenkinsPassword = $compile->token ? $compile->token : base64_decode($compile->password);

        $jenkinsAuth   = '://' . $jenkinsUser . ':' . $jenkinsPassword . '@';
        $jenkinsServer = str_replace('://', $jenkinsAuth, $jenkinsServer);
        $queueUrl      = sprintf('%s/queue/item/%s/api/json', $jenkinsServer, $compile->queue);

        $response = common::http($queueUrl);
        if(strripos($response, "404") > -1)
        {
            $infoUrl  = sprintf("%s/job/%s/api/xml?tree=builds[id,number,result,queueId]&xpath=//build[queueId=%s]", $jenkinsServer, $compile->jkJob, $compile->queue);
            $response = common::http($infoUrl);
            if($response)
            {
                $buildInfo   = simplexml_load_string($response);
                $buildNumber = strtolower($buildInfo->number);
                $result      = strtolower($buildInfo->result);
                $this->updateBuildStatus($compile, $result);

                $logUrl   = sprintf('%s/job/%s/%s/consoleText', $jenkinsServer, $compile->jkJob, $buildNumber);
                $response = common::http($logUrl);
                $this->dao->update(TABLE_COMPILE)->set('logs')->eq($response)->where('id')->eq($compile->id)->exec();
            }
        }
        else
        {
            $queueInfo = json_decode($response);
            if(!empty($queueInfo->executable))
            {
                $buildUrl = $queueInfo->executable->url . 'api/json?pretty=true';
                $buildUrl = str_replace('://', $jenkinsAuth, $buildUrl);

                $response  = common::http($buildUrl);
                $buildInfo = json_decode($response);

                if($buildInfo->building)
                {
                    $this->updateBuildStatus($compile, 'building');
                }
                else
                {
                    $result = strtolower($buildInfo->result);
                    $this->updateBuildStatus($compile, $result);

                    $logUrl   = $buildInfo->url . 'logText/progressiveText/api/json';
                    $logUrl   = str_replace('://', $jenkinsAuth, $logUrl);
                    $response = common::http($logUrl);
                    $this->dao->update(TABLE_COMPILE)->set('logs')->eq($response)->where('id')->eq($compile->id)->exec();
                }
            }
        }
    }

    /**
     * Update ci build status.
     *
     * @param  object $build
     * @param  string $status
     * @access public
     * @return void
     */
    public function updateBuildStatus($build, $status)
    {
        $this->dao->update(TABLE_COMPILE)->set('status')->eq($status)->where('id')->eq($build->id)->exec();
        $this->dao->update(TABLE_JOB)->set('lastExec')->eq(helper::now())->set('lastStatus')->eq($status)->where('id')->eq($build->job)->exec();
    }

    /**
     * Send request.
     * 
     * @param  string $url 
     * @param  object $data 
     * @access public
     * @return int
     */
    public function sendRequest($url, $data)
    {
        if(!empty($data->PARAM_TAG)) $data->PARAM_REVISION = '';

        $response = common::http($url, $data, true);
        if(preg_match("!Location: .*item/(.*)/!", $response, $matches)) return $matches[1];
        return 0;
    }
}
