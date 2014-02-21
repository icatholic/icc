Ext.define('icc.store.common.Boolean', {
	extend : 'Ext.data.Store',
	fields : [ "name", "value" ],
	data : [ {
		"name" : '是',
		"value" : true
	}, {
		"name" : '否',
		"value" : false
	} , {
		"name" : '无',
		"value" : ''
	} ]
});