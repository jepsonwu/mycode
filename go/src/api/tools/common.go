package tools

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


