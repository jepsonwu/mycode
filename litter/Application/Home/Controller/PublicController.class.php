<?php
namespace Home\Controller;

use Think\Controller;

class PublicController extends Controller
{

	/**
	 * 登录
	 */
	public function Login()
	{
		$this->display();
	}

	/**
	 * 生成验证码
	 */
	public function Verify()
	{
		ob_end_clean();
		$verify = new \Think\Verify(C("VERIFY_CONFIG"));
		$verify->entry();
	}

	/**
	 * 登录
	 */
	public function User_login()
	{
		pre("a");
	}

	/**
	 * 图片上传
	 * @param $config 提供一个额外配置参数
	 */
	public function uploadPic($config=array())
	{
		$upload = new \Think\Upload(array_merge(C("UPLOAD_PIC_CONF"), array("savePath" => I("request.folder") . "/"),$config));

		//上传失败
		$info = $upload->upload();
		$info || $this->ajaxReturn("0|" . $upload->getError());
		$info = current($info);

		//取得成功上传的文件信息
		$filename = PUBLIC_UPLOAD_PATH . $info["savepath"] . $info['savename'];
		$width = I("request.width");
		$height = I("request.height");

		$image = new \Think\Image($filename);
		//生成缩略图
		if (I("request.imgRedraw") == 1 && $width && $height)
			$image->thumb($width, $height);

		$size = $image->size();

		$idNow = I("request.id");
		$savename = $info["savepath"] . $info['savename'];

		$data = "1|图片上传成功！|" . (empty($idNow) ? "pic" : $idNow) . "|" . $savename . "|" . $size[0] . "×" . $size[1] . "|" . $info['savename'];
		$this->ajaxReturn($data);

	}

	/**
	 * 附件上传
	 * @param $config
	 */
	public function uploadAtt($config=array())
	{
		$upload = new \Think\Upload(array_merge(C("UPLOAD_ATT_CONF"), array("savePath" => I("request.folder") . "/"),$config));

		$info = $upload->upload();
		$info || $this->ajaxReturn("0|" . $upload->getError());
		$info = current($info);

		$idNow = I("request.id");
		$size_len = strlen($info['size']);
		$size = $info['size'];
		switch ($size_len) {
			case ($size_len >= 8):$size = round($size / 1024 / 1024) . "MB";break;
			case ($size_len >= 4):$size = round($size / 1024) . "KB";break;
			default:$size .= "K";
		}

		$data = "1|资料上传成功！|" . $idNow ? $idNow : "att" . "|" . $info['savename'] . '|' . $size;
		$this->ajaxReturn($data);
	}

}
