Ext.define('icc.store.idatabase.Statistic.One', {
	extend: 'Ext.data.Store',
	autoLoad: false,
	model: 'icc.model.idatabase.Statistic',
	proxy: {
		type: 'ajax',
		url: '/idatabase/statistic/get',
		extraParams: {

		},
		reader: {
			type: 'json',
			root: 'result',
			totalProperty: 'total'
		}
	}
});