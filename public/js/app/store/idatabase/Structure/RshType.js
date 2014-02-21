Ext.define('icc.store.idatabase.Structure.RshType', {
	extend : 'Ext.data.Store',
	fields : [ "name", "val" ],
	data : [ {
		"name" : '下拉菜单',
		"val" : 'combobox'
	}, {
		"name" : '单选框',
		"val" : 'radio'
	}, {
		"name" : '复选框',
		"val" : 'checkbox'
	} ]
});