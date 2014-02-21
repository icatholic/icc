Ext.define('icc.store.idatabase.Project', {
	extend: 'Ext.data.Store',
	requires: ['icc.model.idatabase.Project'],
	autoLoad: false,
	model: 'icc.model.idatabase.Project',
	proxy: {
		type: 'ajax',
		url: '/idatabase/project/index',
		extraParams: {

		},
		reader: {
			type: 'json',
			root: 'result',
			totalProperty: 'total'
		}
	}
});