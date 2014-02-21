Ext.define('icc.model.common.Model', {
	extend : 'Ext.data.Model',
	fields : [ {
		name : '_id',
		type : 'string',
		convert : function(value, record) {
			if (Ext.isObject(value) && value['$id'] != undefined) {
				return value['$id'];
			}
			return value;
		}
	}, {
		name : '__CREATE_TIME__',
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
		name : '__MODIFY_TIME__',
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
		name : '__REMOVED__',
		type : 'boolean'
	} ]
});