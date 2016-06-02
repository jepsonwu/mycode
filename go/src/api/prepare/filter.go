package prepare

import (
	"fmt"
	"go/parser"
	"go/ast"
	"go/token"
	"os"
	"strconv"
	"unicode"
	"strings"
	"github.com/astaxie/beego"
	"api/tools"
	"path/filepath"
	"github.com/astaxie/beego/utils"
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

var (
	ParamFilterDirectory []string
	ParamFilters = make(map[string]ParamFilter)
)

//控制器注释解析 参数校验 签名 等
//需要在mian文件增加
func ParseParamFilters() {
	//获取待解析目录
	for directory, _ := range beego.GlobalControllerRouter {
		directoryArray := strings.Split(directory, ":")
		ParamFilterDirectory = tools.Append(ParamFilterDirectory, directoryArray[0])
	}

	//获取GOPATH 包含多个
	goPath := os.Getenv("GOPATH")
	if goPath == "" {
		panic("you are in dev mode. So please set gopath")
	}
	goPathArray := filepath.SplitList(goPath)

	//解析文件语法树
	for _, directory := range ParamFilterDirectory {
		directoryFilter := ""

		for _, path := range goPathArray {
			path, _ = filepath.EvalSymlinks(filepath.Join(path, "src", directory))
			if utils.FileExists(path) {
				directoryFilter = path
				break;
			}
		}

		if directoryFilter != "" {
			fileSet := token.NewFileSet()
			astPkgs, err := parser.ParseDir(fileSet, directoryFilter, func(info os.FileInfo) bool {
				name := info.Name()
				return !info.IsDir()&&!strings.HasPrefix(name, ".")&&strings.HasSuffix(name, ".go")
			}, parser.ParseComments)

			if err != nil {
				//todo 错误机制
				panic("parse dir failed")
			}

			for _, pkg := range astPkgs {
				for _, astFile := range pkg.Files {
					for _, decl := range astFile.Decls {
						switch specDecl := decl.(type) {
						case *ast.FuncDecl:
							if specDecl.Recv != nil {
								exp, ok := specDecl.Recv.List[0].Type.(*ast.StarExpr)
								if ok {
									parserComments(specDecl.Doc, specDecl.Name.String(), fmt.Sprint(exp.X))
								}
							}
						}
					}
				}
			}
		}
	}
}

//解析注释
func parserComments(comments *ast.CommentGroup, funcName, controllerName string) error {
	if comments != nil &&comments.List != nil {
		for _, c := range comments.List {
			t := strings.TrimSpace(strings.TrimLeft(c.Text, "//"))

			if strings.HasPrefix(t, "@Param") {
				p := getparams(strings.TrimSpace(t[len("@Param "):]), 8)

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