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
// @Param page query int false "list page"
// @Param pagesize query int false "list pagesize"
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
	//todo 参数校验、签名、
	uid := d.GetString(":uid")

	if uid == "" {
		d.FailedJson("1001", "uid is empty")
	}

	uidInt, _ := strconv.Atoi(uid)
	demo, err := models.GetDemo(uint32(uidInt))
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
	page := d.GetString("page")
	fmt.Printf("page value:%v,type:%T", page, page);
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

	d.SuccessJson(map[string]uint32{"uid":uid})
}