package v2

import (
	"github.com/astaxie/beego"
	"api/models"
)

type UserController struct {
	beego.Controller
}

// @Title Get
// @Description get all Users
// @Success 200 {object} models.User
// @router / [get]
func (u *UserController) GetAll() {
	users := models.GetAllUsersDemo()
	u.Data["json"] = users
	u.ServeJSON()
}