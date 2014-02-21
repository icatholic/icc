Ext.define('icc.store.idatabase.Collection.Order', {
	extend: 'Ext.data.Store',
	autoLoad: false,
	model : 'icc.model.idatabase.Collection.Order',
	proxy : {
		type : 'ajax',
		url : '/idatabase/order/index',
		extraParams : {
			
		},
		reader : {
			type : 'json',
			root : 'result',
			totalProperty : 'total'
		}
	}
});