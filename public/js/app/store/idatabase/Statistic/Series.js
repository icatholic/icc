Ext.define('icc.store.idatabase.Statistic.Series', {
	extend : 'Ext.data.Store',
	fields : [ "name", "value" ],
	data : [ {
		"name" : '柱状图',
		"value" : 'column'
	}, {
		"name" : '线形图',
		"value" : 'line'
	} , {
		"name" : '饼状图',
		"value" : 'pie'
	} ]
});