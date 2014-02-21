Ext.define('icc.store.idatabase.Structure.FilterType', {
	extend : 'Ext.data.Store',
	autoLoad: false,
	model : 'icc.model.idatabase.Structure.FilterType',
	proxy : {
		type : 'ajax',
		url : '/idatabase/structure/filter',
		extraParams : {
			
		},
		reader : {
			type : 'json',
			root : 'result',
			totalProperty : 'total'
		}
	}
});