package routers

import (
	"github.com/astaxie/beego"
)

func init() {

	beego.GlobalControllerRouter["api/controllers/v2:UserController"] = append(beego.GlobalControllerRouter["api/controllers/v2:UserController"],
		beego.ControllerComments{
			"GetAll",
			`/`,
			[]string{"get"},
			nil})

}
