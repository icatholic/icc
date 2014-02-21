Ext.define('icc.store.idatabase.Plugin', {
	extend: 'Ext.data.Store',
	autoLoad: false,
	model : 'icc.model.idatabase.Plugin',
	proxy : {
		type : 'ajax',
		url : '/idatabase/plugin/index',
		extraParams : {
			
		},
		reader : {
			type : 'json',
			root : 'result',
			totalProperty : 'total'
		}
	}
});