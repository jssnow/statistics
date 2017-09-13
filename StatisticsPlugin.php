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

        //如果移动了入口文件的位置,则这里文件路径需要修改
        require_once "./plugins/statistics/jpgraph/jpgraph.php";
        require_once "./plugins/statistics/jpgraph/jpgraph_line.php";
        require_once "./plugins/statistics/jpgraph/jpgraph_bar.php";

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
            foreach ($xdata as $k=>$v)
            {
                $ydata[$k] = 0;
            }
        }


        //将要用于图表创建的数据存放在数组中
//        $data = array(19,23,34,38,45,67,711,78,825,837,90,966);
//        $graph = new \Graph(1000,300); //创建新的Graph对象
//        $graph->SetScale("textlin"); //设置刻度样式
//        $graph->img->SetMargin(30,30,80,30); //设置图表边界
//        $graph->title->Set("new users count (" . date("m") . "Month)"); //设置图表标题
//
//        // Create the linear plot
//        $lineplot=new \LinePlot($ydata); // 创建新的LinePlot对象
//        $lineplot->SetLegend("Change"); //设置图例文字
//        $lineplot->SetColor("red"); // 设置曲线的颜色
//
//        // Add the plot to the graph
//        $graph->Add($lineplot); //在统计图上绘制曲线
//        // 加入 x 轴标注
//        $graph->xaxis->SetTickLabels($xdata);
//        // y 轴坐标描点形状为菱形
//        $lineplot-> mark->SetType(MARK_DIAMOND );
//        // 设置 x 轴标注文字为斜体，粗体，6号小字
//        $graph->xaxis->SetFont(FF_ARIAL,FS_BOLD,10);
//        // 阴影效果
//        $graph->SetShadow();
//        // Display the graph
//        $graph->Stroke($register_png); //输出图像
        // Create the graph. These two calls are always required
        $graph = new \Graph(800,350,'auto');
        $graph->SetScale("textlin");

        $theme_class=new \UniversalTheme;
        $graph->SetTheme($theme_class);

//        $graph->yaxis->SetTickPositions(array(0,30,60,90,120,150), array(15,45,75,105,135));
        $graph->SetBox(false);

        $graph->ygrid->SetFill(false);
        $graph->xaxis->SetTickLabels($xdata);
        $graph->yaxis->HideLine(false);
        $graph->yaxis->HideTicks(false,false);

// Create the bar plots
        $b1plot = new \BarPlot($ydata);
//        $b2plot = new BarPlot($data2y);
//        $b3plot = new BarPlot($data3y);

// Create the grouped bar plot
        $gbplot = new \GroupBarPlot(array($b1plot));
// ...and add it to the graPH
        $graph->Add($gbplot);


        $b1plot->SetColor("white");
        $b1plot->SetFillColor("#cc1111");

//        $b2plot->SetColor("white");
//        $b2plot->SetFillColor("#11cccc");
//
//        $b3plot->SetColor("white");
//        $b3plot->SetFillColor("#1111cc");

        $graph->title->Set("Number of new registered users (".$month.")");

// Display the graph
        $graph->Stroke($register_png);

        $this->assign('info', $register_png);
        return [
            'width'  => 12,
            'view'   => $this->fetch('widget'),
            'plugin' => 'Statistic'
        ];
    }
}
