Ext.define('icc.store.idatabase.Statistic', {
	extend: 'Ext.data.Store',
	requires: ['icc.model.idatabase.Statistic'],
	autoLoad: false,
	model: 'icc.model.idatabase.Statistic',
	proxy: {
		type: 'ajax',
		url: '/idatabase/statistic/index',
		extraParams: {

		},
		reader: {
			type: 'json',
			root: 'result',
			totalProperty: 'total'
		}
	}
});