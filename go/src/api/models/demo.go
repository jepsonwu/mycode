package models

import (
	"github.com/astaxie/beego/orm"
	"fmt"
	"time"
)

type Demo struct {
	Uid        uint32 `orm:"pk"`
	Nickname   string `orm:"unique"valid:"Required;MaxSize(50)"` //unique
	Password   string `valid:"Match(/^[A-Za-z0-9_]{32}$/)"`
	Age        uint8   `valid:"Range(1,140)"`
	Phone      string `valid:"Mobile"`
	CreateTime time.Time    `orm:"auto_now_add;type(datetime)"`  //todo 前端传int 后端解析成datetime valid:"Match(/^[0-9]+$/)"
	UpdateTime time.Time        `orm:"auto_now;type(datetime)"`
}

func init() {
	orm.RegisterModel(new(Demo))
}

func GetDemo(uid uint32) (*Demo, error) {
	demo := Demo{Uid:uid}

	err := GetBaseOrmer().Read(&demo)
	if err != nil {
		return nil, err
	}

	return &demo, nil
}

func GetAllDemos() []*Demo {
	var demolist []*Demo

	num, err := GetBaseOrmer().QueryTable("demo").Limit(10).All(&demolist)
	if (err != nil) {
		fmt.Println("query error:", err)
	}else {
		fmt.Println("query num:", num)
	}

	return demolist
}

func AddDemo(d Demo) uint32 {
	uid, err := GetBaseOrmer().Insert(&d)
	if err != nil {
		fmt.Println("demo insert error:", err)
	}

	return uint32(uid)
}
/**
自定义表名 否则使用模型驼峰转蛇形规则
 */
//func (d *Demo)TableName() string {
//	return "user"
//}