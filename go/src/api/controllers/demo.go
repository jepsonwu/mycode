package controllers

import (
	//"github.com/astaxie/beego"
	"api/models"
	"encoding/json"
	"fmt"
	"github.com/astaxie/beego/validation"
	"strconv"
)

type DemoController struct {
	BaseController
}

// @Title Get
// @Description get all Demos
// @Success 200 {object} models.Demo
// @router / [get]
func (d *DemoController)GetAll() {
	demos := models.GetAllDemos()
	d.SuccessJson(demos)
}

// @Title get demo
// @Description get demo
// @Param uid path string true "demo by uid"
// @Success 200 {object} models.Demo
// @Failure 403 :uid is empty
// @router /:uid [get]
func (d *DemoController)Get() {
	uid := d.GetString(":uid")

	if uid == "" {
		d.FailedJson("1001", "uid is empty")
	}

	uidInt, _ := strconv.ParseInt(uid, 0, 64)
	demo, err := models.GetDemo(uidInt)
	if err != nil {
		d.FailedJson("1002", err.Error())
	}

	d.SuccessJson(demo)
}

// @Title create demo
// @Description create demo
// @Param body body models.Demo true "demo content"
// @Success 200 {int} models.Demo.Id
// @Failure 403 body is empty
// @router / [post]
func (d *DemoController)Post() {
	var demo models.Demo
	//todo unmarshal报错怎么办 定义interface{}基本类型结构 json解析成基本类型结构之后再解析成model结构
	err := json.Unmarshal(d.Ctx.Input.RequestBody, &demo)
	if err != nil {
		fmt.Println("demo post body error:", err)
	}

	//value := reflect.ValueOf(demo)
	//for i := 0; i < value.NumField(); i++ {
	//	fmt.Printf("user %v\n\n", value.Field(i))
	//}

	//todo 异常捕获
	//panic(403)

	//参数校验
	valid := validation.Validation{}
	is_valid, err := valid.Valid(&demo)
	if err != nil {
		fmt.Println("demo post valid error:", err)
	}

	if !is_valid {
		for _, err := range valid.Errors {
			fmt.Println("demo post is not valid:", err.Key, err.Message)
		}
		panic(403)
	}

	uid := models.AddDemo(demo)

	//todo common 错误和正确处理
	d.Data["json"] = map[string]int64{"uid":uid}
	d.ServeJSON()
}