Ext.define('icc.store.idatabase.Structure.Type', {
	extend : 'Ext.data.Store',
	fields : [ "name", "val" ],
	data : [ {
		"name" : '单行文字输入框',
		"val" : 'textfield'
	}, {
		"name" : '多行文本输入框',
		"val" : 'textareafield'
	}, {
		"name" : '数字输入框',
		"val" : 'numberfield'
	}, {
		"name" : '是非选择框',
		"val" : 'boolfield'
	}, {
		"name" : '数组',
		"val" : 'arrayfield'
	}, {
		"name" : '内嵌文档',
		"val" : 'documentfield'
	}, {
		"name" : '富文本编辑器',
		"val" : 'htmleditor'
	}, {
		"name" : '日期控件',
		"val" : 'datefield'
	}, {
		"name" : '文件上传控件',
		"val" : 'filefield'
	}, {
		"name" : '二维坐标输入框(地球经纬度)',
		"val" : '2dfield'
	}, {
		"name" : 'MD5密码输入字段',
		"val" : 'md5field'
	}, {
		"name" : 'SHA1密码输入字段',
		"val" : 'sha1field'
	} ]
});