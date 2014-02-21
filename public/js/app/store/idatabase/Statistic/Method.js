Ext.define('icc.store.idatabase.Statistic.Method', {
	extend : 'Ext.data.Store',
	fields : [ "name", "value" ],
	data : [ {
		"name" : '计数',
		"value" : 'count'
	}, {
		"name" : '唯一数',
		"value" : 'distinct'
	}, {
		"name" : '求和',
		"value" : 'sum'
	}, {
		"name" : '均值',
		"value" : 'avg'
	}, {
		"name" : '中位数',
		"value" : 'median'
	}, {
		"name" : '方差',
		"value" : 'variance'
	}, {
		"name" : '标准差',
		"value" : 'standard'
	}, {
		"name" : '最大值',
		"value" : 'max'
	}, {
		"name" : '最小值',
		"value" : 'min'
	} ]
});