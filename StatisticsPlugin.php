<?php
// +----------------------------------------------------------------------
// | Author: xiaozhuang <jhj767658181@gmail.com>
// +----------------------------------------------------------------------
namespace plugins\statistics;

use app\user\model\UserModel;
use cmf\lib\Plugin;
use think\Db;

class StatisticsPlugin extends Plugin
{

    public $info = [
        'name'        => 'Statistics',
        'title'       => '统计信息',
        'description' => '用图表展示一些常用的信息',
        'status'      => 1,
        'author'      => '小庄',
        'version'     => '1.0'
    ];

    public $hasAdmin = 0;//插件是否有后台管理界面

    // 插件安装
    public function install()
    {
        $storageOption = cmf_get_option('admin_dashboard_widgets');
        if (empty($storageOption)) {
            $storageOption = [];
        }

        $storageOption[] = ['name' => 'Statistics','is_system' => 0];
        cmf_set_option('admin_dashboard_widgets', $storageOption);
        return true;//安装成功返回true，失败false
    }

    // 插件卸载
    public function uninstall()
    {
        $storageOption = cmf_get_option('admin_dashboard_widgets');
        if (empty($storageOption)) {
            $storageOption = [];
        }
        foreach ($storageOption as &$v){
            if($v['name'] == 'Statistics'){
                unset($v);
            }
        }
        cmf_set_option('admin_dashboard_widgets', $storageOption);
        return true;//卸载成功返回true，失败false
    }

    /**
     * 准备准备数据
     * @return array
     */
    public function adminDashboard()
    {
        //从数据库中获取一个月内每天的新注册用户的数量
        //处理时间戳获取当月月份作为筛选条件
        $month = date('Y-m',time());
        $prefix = config('database.prefix');
        $res = Db::query("select FROM_UNIXTIME(create_time,'%Y-%m-%d') days,count(id) count from ".$prefix."user WHERE FROM_UNIXTIME(create_time,'%Y-%m') = '".$month."' group by days");
        // x 轴数据，作为 x 轴标注
        $j = date("t"); //获取当前月份天数
        $start_time = strtotime(date('Y-m-01'));  //获取本月第一天时间戳
        $xdata = array();
        for($i=0;$i<$j;$i++)
        {
            $xdata[] = date('Y-m-d',$start_time+$i*86400); //每隔一天赋值给数组
        }
        //处理获取到的数据
        $ydata = array();

        if(!empty($res))
        {
            foreach ($xdata as $k=>&$v)
            {
                foreach ($res as $kk=>$vv)
                {
                    if($v == $vv['days'])
                    {
                        $ydata[$k] = $vv['count'];
                        break;
                    }else{
                        $ydata[$k] = 0;
                        continue;
                    }
                }
                $v = substr($v,-2);
            }
        }else{
            foreach ($xdata as $k=>&$v)
            {
                $v = substr($v,-2);
                $ydata[$k] = 0;
            }
        }
        $this->assign('xdata', json_encode($xdata));
        $this->assign('ydata', json_encode($ydata));
        return [
            'width'  => 12,
            'view'   => $this->fetch('widget'),
            'plugin' => 'Statistic'
        ];
    }
}
