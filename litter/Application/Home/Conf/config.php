<?php
return array(
	//模板配置
	'LAYOUT_ON' => true, // 是否启用布局
	'LAYOUT_NAME' => 'Layout/Public/Layout', // 当前布局名称 默认为layout

	//url 模式
	'URL_MODEL'=>1,

	//verify
	'VERIFY_CONFIG'=>array(
		"useCurve"=>false,
		"useNoise"=>false,
		"length"=>4,
		"imageW"=>"80",
		"imageH"=>"30",
		"fontSize"=>12
	)
);