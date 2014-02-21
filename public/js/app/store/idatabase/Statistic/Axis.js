Ext.define('icc.store.idatabase.Statistic.Axis', {
	extend : 'Ext.data.Store',
	fields : [ "name", "value" ],
	data : [ {
		"name" : '数字范围',
		"value" : 'Numeric'
	}, {
		"name" : '详值/类别',
		"value" : 'Category'
	}, {
		"name" : '时间范围',
		"value" : 'Time'
	} ]
});