package tools

import (
	"reflect"
	"fmt"
)

//todo 每个类型都需要指定 这里不能传递指针？
func Append(slice []string, element string) []string {
	is_set := false;
	for _, val := range slice {
		if val == element {
			is_set = true
			break
		}
	}
	if !is_set {
		slice = append(slice, element)
	}

	return slice
}

func PrintStruct(i interface{}) {
	v := reflect.ValueOf(i)
	t := reflect.TypeOf(i)
	fmt.Println("reflect struct [")
	for i := 0; i < t.NumField(); i++ {
		fmt.Printf("name:%v,tag:%v,value:%v,type:%v\n\n", t.Field(i).Name,
			t.Field(i).Tag, v.Field(i), v.Field(i).Type())
	}
	fmt.Println("]")
}

