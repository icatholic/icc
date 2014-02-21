Ext.define('icc.model.idatabase.Key', {
	extend: 'icc.model.common.Model',
	fields: [{
		name: 'project_id',
		type: 'string'
	}, {
		name: 'name',
		type: 'string'
	}, {
		name: 'desc',
		type: 'string'
	}, {
		name: 'key',
		type: 'string'
	}, {
		name: 'expire',
		type: 'string',
		convert: function(value, record) {
			if (Ext.isObject(value) && value['sec'] != undefined) {
				var date = new Date();
				date.setTime(value.sec * 1000);
				return date;
			} else {
				return value;
			}
		}
	}, {
		name: 'active',
		type: 'boolean'
	},{
		name : 'default',
		type: 'boolean'
	}]
});