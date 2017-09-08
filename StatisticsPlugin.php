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

    public function adminDashboard()
    {
        //图片路径
        $pic_path = ROOT_PATH . DS . 'public' . DS . 'upload' . DS . 'statistics';
        //如果没有用过这个插件则路径不存在则创建文件夹
        if(!is_dir($pic_path)){
            mkdir($pic_path);
        };
        //图片是固定名字,如果已经存在则删除文件,重新生成
        $register_png = $pic_path . DS . 'register.png';
        if(file_exists($register_png)){
            unlink($register_png);
        }
//        $mysql = Db::query("select VERSION() as version");
//        $mysql = $mysql[0]['version'];
//        $mysql = empty($mysql) ? lang('UNKNOWN') : $mysql;
//
//        $version = THINKCMF_VERSION;
//
//        //server infomation
//        $info = [
//            lang('OPERATING_SYSTEM')      => PHP_OS.'sss',
//            lang('OPERATING_ENVIRONMENT') => $_SERVER["SERVER_SOFTWARE"],
//            lang('PHP_VERSION')           => PHP_VERSION,
//            lang('PHP_RUN_MODE')          => php_sapi_name(),
//            lang('PHP_VERSION')           => phpversion(),
//            lang('MYSQL_VERSION')         => $mysql,
//            'ThinkPHP'                    => THINK_VERSION,
//            'ThinkCMF'                    => "{$version} <a href=\"http://www.thinkcmf.com\" target=\"_blank\">访问官网</a>",
//            lang('UPLOAD_MAX_FILESIZE')   => ini_get('upload_max_filesize'),
//            lang('MAX_EXECUTION_TIME')    => ini_get('max_execution_time') . "s",
//            //TODO 增加更多信息
//            lang('DISK_FREE_SPACE')       => round((@disk_free_space(".") / (1024 * 1024)), 2) . 'M',
//            lang('REG_NUM')               => 243,
//        ];
        //如果移动了入口文件的位置,则这里文件路径需要修改
        require_once "./plugins/statistics/jpgraph/jpgraph.php";
        require_once "./plugins/statistics/jpgraph/jpgraph_line.php";
        require_once "./plugins/statistics/jpgraph/jpgraph_bar.php";

        //从数据库中获取一个月内每天的新注册用户的数量
        //处理时间戳获取当月月份作为筛选条件
        $month = date('Ym',time());
        $prefix = config('database.prefix');
        $res = Db::query("select FROM_UNIXTIME(create_time,'%Y%m%d') days,count(id) count from ".$prefix."user WHERE FROM_UNIXTIME(create_time,'%Y%m') = '".$month."' group by days");
        dump($res);
//        exit;
        if(!empty($info)){

        }else{
            //TODO 最近七天没有数据
        }
        // x 轴数据，作为 x 轴标注
        $j = date("t"); //获取当前月份天数
        $start_time = strtotime(date('Y-m-01'));  //获取本月第一天时间戳
        $xdata = array();
        for($i=0;$i<$j;$i++){
            $xdata[] = date('Y-m-d',$start_time+$i*86400); //每隔一天赋值给数组
        }
        //处理获取到的数据
        $ydata = array();

        for ($i=0;$i<33;$i++){
            foreach ($res as $v){
                if(intval(substr($v['days'],-1,2)) == $i){
                    $ydata[$i] = $v['count'];
                }else{
                    $ydata[$i] = 0;
                }
            }
        }
        dump($ydata);
        exit();

        //将要用于图表创建的数据存放在数组中
        $data = array(19,23,34,38,45,67,711,78,825,837,90,966);
        $graph = new \Graph(400,300); //创建新的Graph对象
        $graph->SetScale("textlin"); //设置刻度样式
        $graph->img->SetMargin(30,30,80,30); //设置图表边界
        $graph->title->Set("new users "); //设置图表标题

        // Create the linear plot
        $lineplot=new \LinePlot($data); // 创建新的LinePlot对象
        $lineplot->SetLegend("Amount(M dollars)"); //设置图例文字
        $lineplot->SetColor("red"); // 设置曲线的颜色
        // Add the plot to the graph
        $graph->Add($lineplot); //在统计图上绘制曲线
        // Display the graph
        $graph->Stroke($register_png); //输出图像
        $this->assign('info', $register_png);
        return [
            'width'  => 12,
            'view'   => $this->fetch('widget'),
            'plugin' => 'Statistic'
        ];
    }
}
