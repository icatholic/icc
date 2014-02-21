Ext.define('icc.store.idatabase.Structure', {
	extend: 'Ext.data.Store',
	autoLoad: false,
	model : 'icc.model.idatabase.Structure',
	proxy : {
		type : 'ajax',
		url : '/idatabase/structure/index',
		extraParams : {
			
		},
		reader : {
			type : 'json',
			root : 'result',
			totalProperty : 'total'
		}
	}
});