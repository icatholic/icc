Ext.define('icc.store.idatabase.Collection.Type', {
	extend : 'Ext.data.Store',
	fields : [ "name", "val" ],
	storeId : 'idatabaseCollectionType',
	data : [ {
		"name" : '普通模式：针对系统日常使用人员显示的集合',
		"val" : 'common'
	}, {
		"name" : '专家模式：针对研发或专业类人员使用的集合',
		"val" : 'professional'
	} ]
});