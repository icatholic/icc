Ext.define('icc.store.idatabase.Statistic.Type', {
	extend: 'Ext.data.Store',
	fields: ["name", "value"],
	data: [{
		"name": '全部数据统计',
		"value": 'total'
	}, {
		"name": '按具体值统计',
		"value": 'value'
	}, {
		"name": '按小时统计',
		"value": 'hour'
	}, {
		"name": '按日期统计',
		"value": 'day'
	}, {
		"name": '按月份统计',
		"value": 'month'
	}, {
		"name": '按年份统计',
		"value": 'year'
	}, {
		"name": '按数字范围统计',
		"value": 'range'
	}]
});