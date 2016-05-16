##DM多语言翻译规范

* 命名空间：
	控制器中：认使用结构：module.controller.action.keyword结构，module只带zend中的api、web、admin等module
	Model中：使用model.table.key，如model.member.status.Active
	Module中：使用module.name.key结构，如module.auth.error.invalid
	View中：默认使用view.controller.action.keyword结构
	以上命名空间归属不做强制，合理理由范围内允许自主抽象。譬如module属于controller的某个问题区间，使用控制器的命名空间也是可以的。同一个项目中的合作方要做好约定。
	
* 翻译关键词(token)全部采用以半角点号分割的英文单词组命名，建议全部小写，多个单词可以采用驼峰风格，比如front.trade.form.submit

* PHP控制器文件中默认使用结构：module.controller.action.keyword结构

* 模板中的翻译子句使用结构：module.controller.action.element.part结构，element代表在当前页面上的功能块(优先标注某表单form或某表格table)，part代表里面的具体某个翻译。可以视需要延长。比如表格表头可以使用member.trade.history.list.field.amount

* 翻译单词太长或含有多个单词可以简写或者精简到一个单词。保证命名空间不会冲突的前提下可以适当精简，如将module.controller.action精简为一个识别词。

* 某功能模块的错误提示统一写在module.controller.action.error命名空间下，如module.controller.action.error.name.invalid

* 共用的翻译可以放在common命名空间下。类似类型或状态等ENUM数据类型，统一放在common.enum命名空间下，比如比特币类型：common.enum.coin.bitcoin

* 请注意英文总的单复数区别。zend支持单复数翻译。
