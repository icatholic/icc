Ext.define('icc.store.idatabase.Plugin.System', {
	extend: 'Ext.data.Store',
	autoLoad: false,
	model : 'icc.model.idatabase.Plugin.System',
	proxy : {
		type : 'ajax',
		url : '/idatabase/plugin/read-plugin',
		extraParams : {
			
		},
		reader : {
			type : 'json',
			root : 'result',
			totalProperty : 'total'
		}
	}
});