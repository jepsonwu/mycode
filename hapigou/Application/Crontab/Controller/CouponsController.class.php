<?php
namespace Crontab\Controller;

/**
 *
 * User: jepson <jepson@abc360.com>
 * Date: 15-10-14
 * Time: 上午11:07
 */
class CouponsController
{
	//优惠码初始数字间隔10亿到40亿之间
	protected $_start_num = 1000000000;
	protected $_end_num = 4000000000;

	//一个文件存储250000万个
	protected $_file_total = 250000;

	//写文件频率,一次写多少个优惠码
	protected $_write_total = 1000;

	/**
	 * 批量生成优惠券码
	 * 配置优惠券码递增间隔为3
	 * todo 日志
	 */
	public function coupon_code()
	{
		set_time_limit(0);
		$start_time = time();

		$coupon_id = I("get.coupon_id");
		$coupon_info = "";
		if ($coupon_id)
			$coupon_info = M("Coupons")->where("id='{$coupon_id}'")->field("id,discount_code,total")->find();

		if ($coupon_info) {
			$dir_name = BASE_PATH . "/Uploads/V2/coupon_code/{$coupon_info['id']}/";
			if (!is_dir($dir_name)) {
				mkdir($dir_name, 0757, true);
			}

			//间隔
			$interval = C("COUPONS_INTERVAL");
			//生成优惠码
			$total_file = ceil($coupon_info['total'] / $this->_file_total);
			$add_total = $coupon_info['total'] % $this->_file_total;

			$file_names = array();
			for ($i = 1; $i <= $total_file; $i++) {
				$file_name = $dir_name . "{$coupon_info['discount_code']}_{$i}.txt";
				$file_names[] = $file_name;

				//清空文件 todo
				file_put_contents($file_name, "");

				//1000个写一次文件
				$tmp_coupons = "";
				$write_total = 0;

				$end_j = ($i != $total_file) ? $this->_file_total : $add_total;
				$start_number = $this->_start_num + ($i - 1) * $this->_file_total;
				for ($j = 1; $j <= $end_j; $j++) {
					//初始数据
					$number = $start_number + $j * $interval;

					//如果有位数不等于8 记录
					$code = coupon_code_encrypt($number);
					if (strlen($code) != 8)
						echo "位数不为8,number:{$number},code:$code\n";

					$tmp_coupons .= $coupon_info['discount_code'] . $code . "\n";
					$write_total++;

					//写文件
					if ($write_total > $this->_write_total || $j == $end_j) {
						file_put_contents($file_name, $tmp_coupons, FILE_APPEND);
						$write_total = 0;
						$tmp_coupons = "";
					}
				}

			}

			//打包
			$zip_name = $dir_name . "{$coupon_info['discount_code']}.zip";
			$zip = new \ZipArchive();

			if ($zip->open($zip_name, \ZipArchive::CREATE) === true) {
				foreach ($file_names as $file) {
					$zip->addFile($file, substr($file, strrpos($file, "/") + 1));
				}
			} else {
				echo "zip field";
			}

			$zip->close();
		}


		$end_time = time();
		echo "总耗时：" . ($end_time - $start_time) . "秒";

		M("Coupons")->where("id='{$coupon_id}'")->save(array("multi_code" => 1));
	}
}
