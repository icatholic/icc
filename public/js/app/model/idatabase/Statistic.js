Ext.define('icc.model.idatabase.Statistic', {
	extend : 'icc.model.common.Model',
	fields : [ {
		name : 'name',
		type : 'string'
	}, {
		name : 'yAxisTitle',
		type : 'string'
	}, {
		name : 'yAxisType',
		type : 'string'
	}, {
		name : 'yAxisField',
		type : 'string'
	}, {
		name : 'xAxisTitle',
		type : 'string'
	}, {
		name : 'defaultQuery',
		type : 'string',
		convert : function(value, record) {
			if (Ext.isObject(value) || Ext.isArray(value)) {
				return Ext.JSON.encode(value);
			} else if (Ext.isString(value)) {
				return value;
			} else {
				return '';
			}
		}
	}, {
		name : 'xAxisType',
		type : 'string'
	}, {
		name : 'xAxisField',
		type : 'string'
	}, {
		name : 'seriesType',
		type : 'string'
	}, {
		name : 'seriesField',
		type : 'string'
	}, {
		name : 'seriesXField',
		type : 'string'
	}, {
		name : 'seriesYField',
		type : 'string'
	}, {
		name : 'maxShowNumber',
		type : 'int'
	}, {
		name : 'isDashboard',
		type : 'boolean'
	}, {
		name : 'dashboardTitle',
		type : 'string'
	}, {
		name : 'dashboardQuery',
		type : 'string',
		convert : function(value, record) {
			if (Ext.isObject(value) || Ext.isArray(value)) {
				return Ext.JSON.encode(value);
			} else if (Ext.isString(value)) {
				return value;
			} else {
				return '';
			}
		}
	}, {
		name : 'statisticPeriod',
		type : 'int'
	}, {
		name : 'colspan',
		type : 'int'
	}, {
		name : 'interval',
		type : 'int'
	}, {
		name : 'lastExecuteTime',
		type : 'string',
		convert : function(value, record) {
			if (Ext.isObject(value) && value['sec'] != undefined) {
				var date = new Date();
				date.setTime(value.sec * 1000);
				return date;
			} else {
				return value;
			}
		}
	}, {
		name : 'resultExpireTime',
		type : 'object',
		convert : function(value, record) {
			if (Ext.isObject(value) && value['sec'] != undefined) {
				var date = new Date();
				date.setTime(value.sec * 1000);
				return date;
			} else {
				return value;
			}
		}
	}, {
		name : 'isRunning',
		type : 'boolean'
	} ]
});