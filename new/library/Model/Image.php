<?php
/**
 * 
 * @author Mark
 *
 */
class Model_Image
{
	//图片类型
	public $type;
	//实际宽度
	public $width;
	//实际高度
	public $height;
	//改变后的宽度
	public $resize_width;
	//改变后的高度
	public $resize_height;
	//是否裁图
	public $cut;
	//源图象
	public $srcimg;
	//目标图象地址
	public $dstimg;
	//临时创建的图象
	public $im;

	public function resizeImage($img, $wid, $hei,$c,$dstpath)
	{
		try{
			$this->srcimg = $img;
			$this->resize_width = $wid;
			$this->resize_height = $hei;
			$this->cut = $c;
			//图片的类型

			$imgInfo = getImagesize($img);

			$this->type = $imgInfo[2];

			//初始化图象
			$this->initi_img();
			//目标图象地址
			$this->dstimg = $dstpath;
			$this->width = $imgInfo[0];
			$this->height = $imgInfo[1];
			//生成图象
			$this->newimg();
			ImageDestroy ($this->im);
		}catch(Exception $e){
			return $e->getMessage();
		}
	}
	/*
	 * 生成水印图
	 */
	public function watermarkImage($groundImage,$waterPos=0,$waterImage="")
	{
		try{
			$isWaterImage = FALSE;
			$formatMsg = "暂不支持该文件格式，请用图片处理软件将图片转换为GIF、JPG、PNG格式。";

			//读取水印文件
			if(!empty($waterImage) && file_exists($waterImage)){
				$isWaterImage = TRUE;
				$water_info = getimagesize($waterImage);
				$water_w = $water_info[0];//取得水印图片的宽
				$water_h = $water_info[1];//取得水印图片的高
				switch($water_info[2])//取得水印图片的格式
				{
					case 1:$water_im = imagecreatefromgif($waterImage);break;
					case 2:$water_im = imagecreatefromjpeg($waterImage);break;
					case 3:$water_im = imagecreatefrompng($waterImage);break;
					default:die($formatMsg);
				}
			}
			//读取背景图片
			if(!empty($groundImage) && file_exists($groundImage)){
				$ground_info = getimagesize($groundImage);
				$ground_w = $ground_info[0];//取得背景图片的宽
				$ground_h = $ground_info[1];//取得背景图片的高
				switch($ground_info[2]){//取得背景图片的格式
					case 1:$ground_im = imagecreatefromgif($groundImage);break;
					case 2:$ground_im = imagecreatefromjpeg($groundImage);break;
					case 3:$ground_im = imagecreatefrompng($groundImage);break;
					default:die($formatMsg);
				}
			}else{
				die("需要加水印的图片不存在！");
			}
			//水印位置
			if($isWaterImage){//图片水印
				$w = $water_w;
				$h = $water_h;
				$label = "图片的";
			}

			if( ($ground_w<$w) || ($ground_h<$h) ){
				echo "需要加水印的图片的长度或宽度比水印".$label."还小，无法生成水印！";
				return;
			}
			switch($waterPos){
				case 0://随机
					$posX = rand(0,($ground_w - $w));
					$posY = rand(0,($ground_h - $h));
					break;
				case 1://1为顶端居左
					$posX = 0;
					$posY = 0;
					break;
				case 2://2为顶端居中
					$posX = ($ground_w - $w) / 2;
					$posY = 0;
					break;
				case 3://3为顶端居右
					$posX = $ground_w - $w;
					$posY = 0;
					break;
				case 4://4为中部居左
					$posX = 0;
					$posY = ($ground_h - $h) / 2;
					break;
				case 5://5为中部居中
					$posX = ($ground_w - $w) / 2;
					$posY = ($ground_h - $h) / 2;
					break;
				case 6://6为中部居右
					$posX = $ground_w - $w;
					$posY = ($ground_h - $h) / 2;
					break;
				case 7://7为底端居左
					$posX = 0;
					$posY = $ground_h - $h;
					break;
				case 8://8为底端居中
					$posX = ($ground_w - $w) / 2;
					$posY = $ground_h - $h;
					break;
				case 9://9为底端居右
					$posX = $ground_w - $w - 10;   // -10 是距离右侧10px 可以自己调节
					$posY = $ground_h - $h - 10;   // -10 是距离底部10px 可以自己调节
					break;
				default://随机
					$posX = rand(0,($ground_w - $w));
					$posY = rand(0,($ground_h - $h));
					break;
			}

			//设定图像的混色模式
			imagealphablending($ground_im, true);
			if($isWaterImage){//图片水印
				imagecopy($ground_im, $water_im, $posX, $posY, 0, 0, $water_w,$water_h);//拷贝水印到目标文件
			}

			//生成水印后的图片
			@unlink($groundImage);
			switch($ground_info[2]){//取得背景图片的格式
				case 1:imagegif($ground_im,$groundImage);break;
				case 2:imagejpeg($ground_im,$groundImage);break;
				case 3:imagepng($ground_im,$groundImage);break;
				default:die($errorMsg);
			}

			//释放内存
			if(isset($water_info)) unset($water_info);
			if(isset($water_im)) imagedestroy($water_im);
			unset($ground_info);
			imagedestroy($ground_im);
		}catch(Exception $e){
			return $e->getMessage();
		}
	}

	private function newimg()
	{
		//改变后的图象的比例
		$resize_ratio = ($this->resize_width)/($this->resize_height);
		//实际图象的比例
		$ratio = ($this->width)/($this->height);
		if(($this->cut)=="1")
		//裁图
		{
			if($ratio>=$resize_ratio)
			//高度优先
			{
				$newimg = imagecreatetruecolor($this->resize_width,$this->resize_height);
				imagecopyresampled($newimg, $this->im, 0, 0, 0, 0, $this->resize_width,$this->resize_height, (($this->height)*$resize_ratio), $this->height);
				ImageJpeg ($newimg,$this->dstimg);
			}
			if($ratio<$resize_ratio)
			//宽度优先
			{
				$newimg = imagecreatetruecolor($this->resize_width,$this->resize_height);
				imagecopyresampled($newimg, $this->im, 0, 0, 0, 0, $this->resize_width, $this->resize_height, $this->width, (($this->width)/$resize_ratio));
				ImageJpeg ($newimg,$this->dstimg);
			}
		}
		else
		//不裁图
		{
			if($ratio>=$resize_ratio)
			{
				$newimg = imagecreatetruecolor($this->resize_width,($this->resize_width)/$ratio);
				imagecopyresampled($newimg, $this->im, 0, 0, 0, 0, $this->resize_width, ($this->resize_width)/$ratio, $this->width, $this->height);
				ImageJpeg ($newimg,$this->dstimg);
			}
			if($ratio<$resize_ratio)
			{
				$newimg = imagecreatetruecolor(($this->resize_height)*$ratio,$this->resize_height);
				imagecopyresampled($newimg, $this->im, 0, 0, 0, 0, ($this->resize_height)*$ratio, $this->resize_height, $this->width, $this->height);
				ImageJpeg ($newimg,$this->dstimg);
			}
		}
	}
	//初始化图象
	private function initi_img()
	{
		$creator = "imagecreatefrom";
		$mimeType = explode("/",image_type_to_mime_type($this->type));
		if(preg_match("/bmp/",$mimeType[1])){
			throw new Exception("img type is not right");
		}else{
			$creator = $creator.$mimeType[1];
		}
		$this->im = $creator($this->srcimg);
	}
}