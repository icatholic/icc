Ext.define('icc.store.idatabase.Index', {
	extend: 'Ext.data.Store',
	requires : ['icc.model.idatabase.Index'],
	autoLoad: false,
	model : 'icc.model.idatabase.Index',
	proxy : {
		type : 'ajax',
		url : '/idatabase/index/index',
		reader : {
			type : 'json',
			root : 'result',
			totalProperty : 'total'
		}
	}
});