<?php
namespace Modules\Monitor\Controllers;

use Illuminate\Routing\Controller;
use Modules\Monitor\Repositories\DBRepository;

class DatabaseMonitorController extends Controller
{
	private $db;

	/**
	 * Monitor Controller Constructor
	 * @param MonitorService $monitorService 
	 */
	public function __construct(DBRepository $dbRepository)
	{
		$this->db = $dbRepository;
	}

	/**
	 * Index , Get main info
	 * @return \Response
	 */
	public function index()
	{
		$info = $this->db->getDBInfo();
		$slow = $this->db->getSlowQueries();
		$latest = $this->db->getLatestQueries();
		return view('modules-monitor::db',[
			'info'=>$info,
			'slow'=>$slow,
			'latest'=>$latest,
			'converter'=>function($size){
				$unit=array('B','KB','MB','GB','TB','PB');
	    		return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
			}
		]);
	}
}