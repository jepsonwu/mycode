<?php
class DM_Helper_Thumb
{
	public function thumb($img,$size='')
	{
	    if($size)return  DM_UPLOAD_PATH.$img.$size.'.'.pathinfo($img,PATHINFO_EXTENSION);
	    return  DM_UPLOAD_PATH.$img;
	}
}