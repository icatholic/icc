Ext.define('icc.store.idatabase.Key', {
	extend: 'Ext.data.Store',
	requires : ['icc.model.idatabase.Key'],
	autoLoad: false,
	model : 'icc.model.idatabase.Key',
	proxy : {
		type : 'ajax',
		url : '/idatabase/key/index',
		reader : {
			type : 'json',
			root : 'result',
			totalProperty : 'total'
		}
	}
});