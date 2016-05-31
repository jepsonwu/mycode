package controllers

import (
	"github.com/astaxie/beego"
	"os"
	"go/token"
	"go/parser"
	"go/ast"
	"fmt"
	"strings"
	"unicode"
	"strconv"
	"reflect"
)

//page query int false "list page" "Required;Min(1)" "list page is error" 1
type ParamFilter struct {
	Name        string
	ParamType   string // path,query,body,header,form
	DataType    string
	Required    bool
	Description string
	Rule        string
	ErrorMsg    string
	Default     string
}

type BaseController struct {
	beego.Controller
}

var ParamFilters map[string]ParamFilter

func (c *BaseController)Prepare() {
	//获取当前文件名称
	goPath := os.Getenv("GOPATH")
	if goPath == "" {
		panic("you are in dev mode. So please set gopath")
	}
	controllerFile := "/data/mycode/go/src/api/controllers/demo.go"

	//解析文件语法树
	fileSet := token.NewFileSet()
	astFile, err := parser.ParseFile(fileSet, controllerFile, nil, parser.ParseComments)
	if err != nil {
		panic(err);
	}

	//根据注释得到参数map[action]struct
	for _, d := range astFile.Decls {
		switch specDecl := d.(type) {
		case *ast.FuncDecl:
			if specDecl.Recv != nil {
				exp, ok := specDecl.Recv.List[0].Type.(*ast.StarExpr)
				if ok {
					parserComments(specDecl.Doc, specDecl.Name.String(), fmt.Sprint(exp.X))
				}
			}
		}
	}

	//参数过滤
	for action, filter := range ParamFilters {
		fmt.Printf("param filter action:%v\n\n", action)
		value := reflect.ValueOf(filter)
		for i := 0; i < value.NumField(); i++ {
			fmt.Printf("param filter value type:%T,value:%v\n\n", value.Field(i), value.Field(i))
		}
	}
}

func parserComments(comments *ast.CommentGroup, funcName, controllerName string) error {
	if comments != nil &&comments.List != nil {
		for _, c := range comments.List {
			t := strings.TrimSpace(strings.TrimLeft(c.Text, "//"))

			if strings.HasPrefix(t, "@Param") {
				p := getparams(strings.TrimSpace(t[len("@Param "):]), 8)

				for _, value := range p {
					fmt.Printf("param value %v,count:%d\n\n", value, len(p))
				}

				os.Exit(2)
				if len(p) < 7 {
					panic(controllerName + "_" + funcName + "'s comments @Param at least should has 7 params")
				}

				param := ParamFilter{}
				param.Name = p[0]
				param.ParamType = p[1]
				pp := strings.Split(p[2], ".")
				param.DataType = pp[len(pp) - 1]
				param.Required, _ = strconv.ParseBool(p[3])
				param.Description = p[4]
				param.Rule = p[5]
				param.ErrorMsg = p[6]

				if len(p) > 7 {
					param.Default = p[7]
				}

				ParamFilters[funcName] = param
			}
		}
	}

	return nil
}

//获取配置参数
func getparams(str string, count uint8) []string {
	var s []rune
	var j uint8
	var start bool
	var r []string
	quot_count := 0
	for i, c := range []rune(str) {
		//双引号代表描述 如果双引号的次数为0或者是二的倍数
		if strings.EqualFold(string(c), "\"") {
			quot_count++
		}

		if unicode.IsSpace(c)&&quot_count != 1 {
			if !start {
				continue
			} else {
				start = false
				quot_count = 0
				j++
				r = append(r, string(s))
				s = make([]rune, 0)
				if j == count - 1 {
					r = append(r, strings.TrimSpace((str[i + 1:])))
					break
				}
				continue
			}
		}
		start = true
		s = append(s, c)
	}
	return r
}

func (c *BaseController)FailedJson(code, msg string) {
	c.Data["json"] = map[string]string{"code":code, "msg":msg}
	c.ServeJSON()
	//c.StopRun()
}

func (c *BaseController)SuccessJson(data interface{}) {
	c.Data["json"] = map[string]interface{}{"code":"200", "data":data}
	c.ServeJSON()
}
